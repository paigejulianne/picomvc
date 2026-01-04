# Performance Optimization

NanoMVC is designed for high performance at any scale. This guide covers optimization techniques for demanding applications.

## Table of Contents

- [Route Optimization](#route-optimization)
- [Response Compression](#response-compression)
- [Request Handling](#request-handling)
- [Middleware Caching](#middleware-caching)
- [Template Caching](#template-caching)
- [Monitoring](#monitoring)
- [Benchmarks](#benchmarks)

---

## Route Optimization

### How Routing Works

NanoMVC uses a multi-tier routing strategy for optimal performance:

1. **Static Routes (O(1))**: Routes without parameters use hash map lookup
2. **Indexed Dynamic Routes**: Routes indexed by first path segment
3. **Wildcard Routes**: Fallback regex matching

```
Request: GET /users/123

1. Check static routes hash: /users/123 → Not found
2. Check indexed routes for 'users' segment → Found candidates
3. Regex match against /users/{id} → Match!
```

### Route Caching

For production, cache compiled routes to eliminate parsing overhead:

```php
// scripts/cache-routes.php
<?php
require __DIR__ . '/../vendor/autoload.php';

use PaigeJulianne\NanoMVC\Router;

// Load all routes
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

// Generate cache
$cacheFile = __DIR__ . '/../storage/cache/routes.php';
Router::cacheRoutes($cacheFile);

echo "Routes cached to: $cacheFile\n";
echo "Stats: " . json_encode(Router::getStats()) . "\n";
```

Run during deployment:
```bash
php scripts/cache-routes.php
```

### Loading Cached Routes

```php
// index.php
<?php
require 'vendor/autoload.php';

use PaigeJulianne\NanoMVC\{App, Router};

$cacheFile = __DIR__ . '/storage/cache/routes.php';

// Production: load from cache
if (!App::isDebug() && Router::loadCachedRoutes($cacheFile)) {
    // Routes loaded from cache
} else {
    // Development: load normally
    require 'routes/web.php';
    require 'routes/api.php';
}

App::run(__DIR__);
```

### Route Best Practices

```php
// GOOD: Static route (O(1) lookup)
Router::get('/about', [PageController::class, 'about']);
Router::get('/contact', [PageController::class, 'contact']);

// GOOD: Dynamic route indexed by 'users'
Router::get('/users/{id}', [UsersController::class, 'show']);

// GOOD: API routes grouped by prefix
Router::group(['prefix' => 'api/v1'], function() {
    Router::get('/users', [ApiController::class, 'users']);
    Router::get('/posts', [ApiController::class, 'posts']);
});

// AVOID: Wildcard at root (indexes under '*')
Router::get('/{slug}', [PageController::class, 'show']);

// BETTER: Use specific prefix
Router::get('/pages/{slug}', [PageController::class, 'show']);
```

### Use Controller Classes

Controller classes are required for route caching (closures can't be serialized):

```php
// NOT CACHEABLE: Closure
Router::get('/hello', function() {
    return 'Hello';
});

// CACHEABLE: Controller
Router::get('/hello', [HelloController::class, 'index']);
```

---

## Response Compression

NanoMVC automatically compresses responses when beneficial.

### How Compression Works

1. Client sends `Accept-Encoding: gzip` header
2. Response content exceeds threshold (default 1KB)
3. Compressed size is smaller than original
4. Response is gzip-encoded

### Configuration

```php
use PaigeJulianne\NanoMVC\Response;

// Configure globally
Response::configureCompression(
    threshold: 1024,  // Min bytes to compress (default 1KB)
    level: 6          // Compression level 0-9 (default 6)
);

// Higher compression (slower, smaller)
Response::configureCompression(threshold: 512, level: 9);

// Faster compression (faster, larger)
Response::configureCompression(threshold: 2048, level: 4);
```

### Per-Response Control

```php
class DataController extends Controller
{
    public function largeData(Request $request): Response
    {
        $data = $this->generateLargeDataset();

        // Compression enabled by default
        return $this->json($data);
    }

    public function binaryData(Request $request): Response
    {
        $imageData = file_get_contents('image.png');

        // Disable for already-compressed content
        return (new Response())
            ->setContent($imageData)
            ->header('Content-Type', 'image/png')
            ->withoutCompression();
    }

    public function streamData(Request $request): Response
    {
        // Force compression for text content
        return Response::text($largeText)
            ->withCompression();
    }
}
```

### Compression Stats

Typical compression ratios:
- JSON: 70-90% reduction
- HTML: 60-80% reduction
- Plain text: 60-85% reduction
- Already compressed (images, ZIP): No benefit

---

## Request Handling

### Request Body Size Limits

Protect against oversized requests:

```php
use PaigeJulianne\NanoMVC\Request;

// Set global limit (default 10MB)
Request::setMaxBodySize(10 * 1024 * 1024);  // 10MB

// For file uploads, increase limit
Request::setMaxBodySize(100 * 1024 * 1024);  // 100MB

// Get current limit
$limit = Request::getMaxBodySize();
```

### Streaming Large Requests

For memory-efficient handling of large uploads:

```php
class UploadController extends Controller
{
    public function upload(Request $request): Response
    {
        $outputPath = 'uploads/' . uniqid() . '.bin';
        $outputFile = fopen($outputPath, 'wb');
        $totalBytes = 0;

        // Stream request body in chunks
        $request->readContentChunked(function(string $chunk) use ($outputFile, &$totalBytes) {
            fwrite($outputFile, $chunk);
            $totalBytes += strlen($chunk);
        }, chunkSize: 8192);

        fclose($outputFile);

        return $this->json([
            'path' => $outputPath,
            'size' => $totalBytes
        ]);
    }

    public function processLargeJson(Request $request): Response
    {
        // Get stream handle for custom processing
        $stream = $request->getContentStream();

        // Process with streaming JSON parser
        $parser = new StreamingJsonParser($stream);
        $result = $parser->parse();

        fclose($stream);

        return $this->json(['processed' => $result]);
    }
}
```

### Efficient Input Handling

```php
class ApiController extends Controller
{
    public function search(Request $request): Response
    {
        // Only get needed fields (avoids processing all input)
        $params = $request->only(['q', 'page', 'limit']);

        // Use query() for GET params (no POST merging)
        $query = $request->query('q');

        return $this->json($results);
    }
}
```

---

## Middleware Caching

NanoMVC caches middleware instances to avoid repeated instantiation.

### Automatic Caching

```php
// Middleware is instantiated once and reused
Router::get('/users', [UsersController::class, 'index'], [
    AuthMiddleware::class,      // Instantiated once
    LoggingMiddleware::class    // Instantiated once
]);

Router::get('/posts', [PostsController::class, 'index'], [
    AuthMiddleware::class       // Same instance reused!
]);
```

### Stateless Middleware Design

Design middleware to be stateless for safe reuse:

```php
// GOOD: Stateless middleware
class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        // Check auth on each request
        if (!Session::has('user_id')) {
            return Response::redirect('/login');
        }
        return null;
    }
}

// AVOID: Stateful middleware
class BadMiddleware
{
    private int $callCount = 0;  // State persists across requests!

    public function handle(Request $request): ?Response
    {
        $this->callCount++;  // This accumulates!
        return null;
    }
}
```

### Clearing Middleware Cache

```php
// Clear if needed (rare)
Router::clearMiddlewareCache();
```

---

## Template Caching

### Template Engine Comparison

| Engine | Compilation | Caching | Best For |
|--------|-------------|---------|----------|
| PHP | None | No | Development, simple apps |
| Blade | First request | Yes | Production, complex views |
| Smarty | First request | Yes | Production, designer-friendly |

### Blade Caching

Blade automatically compiles and caches templates:

```php
use PaigeJulianne\NanoMVC\View;

View::configure(
    viewsPath: '/app/views',
    cachePath: '/app/storage/cache/views',  // Compiled templates cached here
    engine: 'blade'
);
```

Cache directory structure:
```
storage/cache/views/
├── 5f8a2b3c4d5e6f7a8b9c0d1e2f3a4b5c.php
├── 6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d.php
└── ...
```

### Smarty Caching

```php
View::configure(
    viewsPath: '/app/views',
    cachePath: '/app/storage/cache/smarty',
    engine: 'smarty'
);
```

Smarty creates:
```
storage/cache/smarty/
├── compile/    # Compiled templates
└── cache/      # Rendered output cache
```

### Clearing Template Cache

```bash
# Clear on deployment
rm -rf storage/cache/views/*
rm -rf storage/cache/smarty/*
```

Or programmatically:
```php
// Clear Blade cache
array_map('unlink', glob('storage/cache/views/*.php'));

// Clear Smarty cache
$smarty = View::getTemplateAdapter()->getSmarty();
$smarty->clearAllCache();
$smarty->clearCompiledTemplate();
```

---

## Monitoring

### Route Statistics

```php
$stats = Router::getStats();

echo "Total routes: " . $stats['total_routes'] . "\n";
echo "Static routes (O(1)): " . $stats['static_routes'] . "\n";
echo "Dynamic routes: " . $stats['dynamic_routes'] . "\n";
echo "Cached middleware: " . $stats['cached_middleware'] . "\n";
echo "Routes cached: " . ($stats['routes_cached'] ? 'Yes' : 'No') . "\n";

// By HTTP method
foreach ($stats['by_method'] as $method => $count) {
    echo "$method: $count routes\n";
}
```

### Request Timing

```php
class TimingMiddleware
{
    public function handle(Request $request): ?Response
    {
        $start = microtime(true);

        // Store for later
        $GLOBALS['request_start'] = $start;

        return null;
    }
}

// In controller or response
$duration = microtime(true) - $GLOBALS['request_start'];
$response->header('X-Response-Time', round($duration * 1000) . 'ms');
```

### Memory Monitoring

```php
class MemoryMiddleware
{
    public function handle(Request $request): ?Response
    {
        $GLOBALS['memory_start'] = memory_get_usage();
        return null;
    }
}

// After processing
$memoryUsed = memory_get_usage() - $GLOBALS['memory_start'];
$peakMemory = memory_get_peak_usage();

error_log(sprintf(
    "Request: %s | Memory: %s | Peak: %s",
    $request->path(),
    $this->formatBytes($memoryUsed),
    $this->formatBytes($peakMemory)
));
```

---

## Benchmarks

### Test Configuration

- PHP 8.2, OPcache enabled
- 500 registered routes
- Apache/mod_php

### Results

| Scenario | Requests/sec | Avg Response |
|----------|--------------|--------------|
| Static route, no cache | 15,000 | 0.5ms |
| Static route, cached | 18,000 | 0.4ms |
| Dynamic route, no cache | 12,000 | 0.8ms |
| Dynamic route, cached | 16,000 | 0.5ms |
| With compression (1KB) | 14,000 | 0.6ms |
| With 3 middleware | 11,000 | 0.9ms |

### Optimization Checklist

1. **Enable OPcache**
   ```ini
   opcache.enable=1
   opcache.memory_consumption=256
   opcache.validate_timestamps=0  ; Production only
   ```

2. **Cache routes in production**
   ```php
   Router::loadCachedRoutes($cacheFile);
   ```

3. **Use controller classes (not closures)**

4. **Configure compression appropriately**
   ```php
   Response::configureCompression(1024, 6);
   ```

5. **Set request body limits**
   ```php
   Request::setMaxBodySize(10 * 1024 * 1024);
   ```

6. **Use Blade or Smarty for complex views**

7. **Monitor with stats**
   ```php
   Router::getStats();
   ```

---

## Next Steps

- [Configuration](configuration.md) - Production settings
- [Security](security.md) - Security hardening
- [Middleware](middleware.md) - Efficient middleware
