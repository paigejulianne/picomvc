# Middleware

Middleware provides a mechanism for filtering HTTP requests entering your application and modifying responses leaving it.

## Table of Contents

- [How Middleware Works](#how-middleware-works)
- [Creating Middleware](#creating-middleware)
- [Registering Middleware](#registering-middleware)
- [Built-in Middleware](#built-in-middleware)
- [Middleware Parameters](#middleware-parameters)
- [Middleware Best Practices](#middleware-best-practices)

---

## How Middleware Works

Middleware sits between the incoming request and your route handler. Each middleware can:

1. **Inspect the request** and optionally reject it
2. **Modify the request** before passing it on
3. **Pass to the next handler** by returning `null`
4. **Return a response** to short-circuit the pipeline

```
Request → Middleware 1 → Middleware 2 → Controller → Response
              ↓               ↓              ↓
         (can reject)   (can reject)   (generates response)
```

---

## Creating Middleware

### Basic Structure

```php
<?php
use PaigeJulianne\NanoMVC\Request;
use PaigeJulianne\NanoMVC\Response;

class MyMiddleware
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request The incoming request
     * @return Response|null Return Response to stop, null to continue
     */
    public function handle(Request $request): ?Response
    {
        // Your logic here

        // Return null to continue to next middleware/handler
        return null;

        // Or return Response to stop and respond immediately
        // return Response::json(['error' => 'Unauthorized'], 401);
    }
}
```

### Authentication Middleware

```php
<?php
use PaigeJulianne\NanoMVC\{Request, Response, Session};

class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (!Session::has('user_id')) {
            if ($request->expectsJson()) {
                return Response::json([
                    'error' => 'Unauthorized',
                    'message' => 'Authentication required'
                ], 401);
            }

            // Store intended URL for redirect after login
            Session::set('intended_url', $request->path());

            return Response::redirect('/login');
        }

        return null;  // User is authenticated, continue
    }
}
```

### Admin Middleware

```php
<?php
class AdminMiddleware
{
    public function handle(Request $request): ?Response
    {
        $user = Session::get('user');

        if (!$user || $user['role'] !== 'admin') {
            if ($request->expectsJson()) {
                return Response::json([
                    'error' => 'Forbidden',
                    'message' => 'Admin access required'
                ], 403);
            }

            return Response::redirect('/dashboard');
        }

        return null;
    }
}
```

### Logging Middleware

```php
<?php
class LoggingMiddleware
{
    public function handle(Request $request): ?Response
    {
        $start = microtime(true);

        // Store for later use
        $GLOBALS['request_start'] = $start;

        // Log request
        error_log(sprintf(
            "[%s] %s %s",
            date('Y-m-d H:i:s'),
            $request->method(),
            $request->path()
        ));

        return null;
    }
}
```

### API Key Middleware

```php
<?php
class ApiKeyMiddleware
{
    private string $validKey;

    public function __construct(string $apiKey)
    {
        $this->validKey = $apiKey;
    }

    public function handle(Request $request): ?Response
    {
        $providedKey = $request->header('X-API-Key')
            ?? $request->query('api_key');

        if (!$providedKey || !hash_equals($this->validKey, $providedKey)) {
            return Response::json([
                'error' => 'Invalid API key'
            ], 401);
        }

        return null;
    }
}
```

---

## Registering Middleware

### Per-Route Middleware

```php
use PaigeJulianne\NanoMVC\Router;

// Single middleware
Router::get('/dashboard', [DashboardController::class, 'index'], [
    AuthMiddleware::class
]);

// Multiple middleware (executed in order)
Router::get('/admin', [AdminController::class, 'index'], [
    AuthMiddleware::class,
    AdminMiddleware::class,
    LoggingMiddleware::class
]);

// Middleware instance with parameters
Router::get('/api/data', [ApiController::class, 'data'], [
    new ApiKeyMiddleware('secret-key-here')
]);
```

### Group Middleware

```php
// All routes in group share middleware
Router::group(['middleware' => [AuthMiddleware::class]], function() {
    Router::get('/profile', [ProfileController::class, 'index']);
    Router::get('/settings', [SettingsController::class, 'index']);
    Router::put('/settings', [SettingsController::class, 'update']);
});

// Nested groups inherit and add middleware
Router::group(['prefix' => 'admin', 'middleware' => [AuthMiddleware::class]], function() {

    // Admin dashboard (requires auth only)
    Router::get('/', [AdminController::class, 'dashboard']);

    // Super admin routes (requires auth + admin)
    Router::group(['middleware' => [AdminMiddleware::class]], function() {
        Router::get('/users', [AdminController::class, 'users']);
        Router::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    });
});
```

### Inline Middleware (Closures)

```php
Router::get('/quick-check', [Controller::class, 'method'], [
    function(Request $request) {
        if ($request->query('secret') !== 'magic') {
            return Response::json(['error' => 'Access denied'], 403);
        }
        return null;
    }
]);
```

---

## Built-in Middleware

### ThrottleMiddleware (Rate Limiting)

```php
use PaigeJulianne\NanoMVC\ThrottleMiddleware;

// 60 requests per minute
Router::get('/api/search', [SearchController::class, 'index'], [
    new ThrottleMiddleware(60, 1)  // 60 requests per 1 minute
]);

// 100 requests per hour
Router::get('/api/heavy', [HeavyController::class, 'process'], [
    new ThrottleMiddleware(100, 60)  // 100 requests per 60 minutes
]);

// Rate limit response (429 Too Many Requests):
// {
//     "error": "Too many requests",
//     "retry_after": 45
// }
// Headers: Retry-After: 45
```

### CsrfMiddleware (CSRF Protection)

```php
use PaigeJulianne\NanoMVC\CsrfMiddleware;

// Protect all POST/PUT/DELETE routes
Router::group(['middleware' => [new CsrfMiddleware()]], function() {
    Router::post('/users', [UsersController::class, 'store']);
    Router::put('/users/{id}', [UsersController::class, 'update']);
    Router::delete('/users/{id}', [UsersController::class, 'destroy']);
});

// Exclude specific paths (webhooks, API)
$csrf = new CsrfMiddleware([
    '/api/*',              // Exclude all /api routes
    '/webhooks/*',         // Exclude webhooks
    '/stripe/callback'     // Exclude specific path
]);

Router::group(['middleware' => [$csrf]], function() {
    // Protected routes
});
```

Include token in forms:
```html
<form method="POST" action="/users">
    <input type="hidden" name="_token" value="<?= Session::csrfToken() ?>">
    <!-- form fields -->
</form>
```

Or in AJAX headers:
```javascript
fetch('/users', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
});
```

### CorsMiddleware (Cross-Origin Requests)

```php
use PaigeJulianne\NanoMVC\CorsMiddleware;

// Default CORS (allows all origins)
Router::group(['prefix' => 'api', 'middleware' => [new CorsMiddleware()]], function() {
    Router::get('/users', [ApiController::class, 'users']);
});

// Configured CORS
$cors = new CorsMiddleware([
    'allowedOrigins' => ['https://example.com', 'https://app.example.com'],
    'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowedHeaders' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'allowCredentials' => true,
    'maxAge' => 86400  // Cache preflight for 24 hours
]);

Router::group(['prefix' => 'api', 'middleware' => [$cors]], function() {
    // API routes
});
```

---

## Middleware Parameters

### Constructor Parameters

```php
class RoleMiddleware
{
    private array $allowedRoles;

    public function __construct(array $roles)
    {
        $this->allowedRoles = $roles;
    }

    public function handle(Request $request): ?Response
    {
        $user = Session::get('user');
        $userRole = $user['role'] ?? 'guest';

        if (!in_array($userRole, $this->allowedRoles)) {
            return Response::json(['error' => 'Insufficient permissions'], 403);
        }

        return null;
    }
}

// Usage
Router::get('/admin', [AdminController::class, 'index'], [
    new RoleMiddleware(['admin', 'superadmin'])
]);

Router::get('/reports', [ReportsController::class, 'index'], [
    new RoleMiddleware(['admin', 'manager', 'analyst'])
]);
```

### Configurable Middleware

```php
class MaintenanceMiddleware
{
    private bool $enabled;
    private array $allowedIps;

    public function __construct(bool $enabled = false, array $allowedIps = [])
    {
        $this->enabled = $enabled;
        $this->allowedIps = $allowedIps;
    }

    public function handle(Request $request): ?Response
    {
        if (!$this->enabled) {
            return null;
        }

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';

        if (in_array($clientIp, $this->allowedIps)) {
            return null;  // Allowed through
        }

        return Response::html(
            '<h1>Site Under Maintenance</h1><p>We\'ll be back soon.</p>',
            503
        );
    }
}

// Usage
$maintenance = new MaintenanceMiddleware(
    enabled: getenv('MAINTENANCE_MODE') === 'true',
    allowedIps: ['127.0.0.1', '10.0.0.1']
);

Router::group(['middleware' => [$maintenance]], function() {
    // All routes
});
```

---

## Middleware Best Practices

### 1. Keep Middleware Stateless

Middleware instances are cached and reused. Avoid storing request-specific state.

```php
// BAD: Stateful middleware
class BadMiddleware
{
    private int $requestCount = 0;  // Accumulates across requests!

    public function handle(Request $request): ?Response
    {
        $this->requestCount++;  // Bug: persists between requests
        return null;
    }
}

// GOOD: Stateless middleware
class GoodMiddleware
{
    public function handle(Request $request): ?Response
    {
        // Use $GLOBALS or request attributes for per-request data
        $GLOBALS['request_id'] = uniqid();
        return null;
    }
}
```

### 2. Order Matters

Middleware executes in the order registered:

```php
// Authentication first, then authorization
Router::get('/admin', $handler, [
    AuthMiddleware::class,    // 1. Check if logged in
    AdminMiddleware::class,   // 2. Check if admin
    LoggingMiddleware::class  // 3. Log the request
]);
```

### 3. Fail Fast

Return early for invalid requests:

```php
class ValidateJsonMiddleware
{
    public function handle(Request $request): ?Response
    {
        // Only validate POST/PUT/PATCH with JSON content
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return null;
        }

        $contentType = $request->header('Content-Type', '');
        if (strpos($contentType, 'application/json') === false) {
            return null;  // Not JSON, let handler deal with it
        }

        // Validate JSON is parseable
        $body = $request->getContent();
        if ($body && json_decode($body) === null && json_last_error() !== JSON_ERROR_NONE) {
            return Response::json([
                'error' => 'Invalid JSON',
                'message' => json_last_error_msg()
            ], 400);
        }

        return null;
    }
}
```

### 4. Use Appropriate Status Codes

```php
// 401 Unauthorized - Authentication required
if (!$isLoggedIn) {
    return Response::json(['error' => 'Authentication required'], 401);
}

// 403 Forbidden - Authenticated but not allowed
if (!$hasPermission) {
    return Response::json(['error' => 'Access denied'], 403);
}

// 429 Too Many Requests - Rate limited
if ($rateLimited) {
    return Response::json(['error' => 'Too many requests'], 429)
        ->header('Retry-After', $retryAfter);
}

// 503 Service Unavailable - Maintenance mode
if ($maintenanceMode) {
    return Response::html($maintenancePage, 503);
}
```

### 5. Document Your Middleware

```php
/**
 * Ensures the request includes a valid API key.
 *
 * Checks for API key in:
 * 1. X-API-Key header
 * 2. api_key query parameter
 *
 * Returns 401 if key is missing or invalid.
 *
 * @example
 * Router::get('/api/data', $handler, [
 *     new ApiKeyMiddleware('your-secret-key')
 * ]);
 */
class ApiKeyMiddleware
{
    // ...
}
```

---

## Complete Example

```php
<?php
// middleware/AuthMiddleware.php
class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (!Session::has('user_id')) {
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Unauthorized'], 401);
            }
            Session::set('intended_url', $request->path());
            return Response::redirect('/login');
        }
        return null;
    }
}

// middleware/RoleMiddleware.php
class RoleMiddleware
{
    private array $roles;

    public function __construct(array $roles)
    {
        $this->roles = $roles;
    }

    public function handle(Request $request): ?Response
    {
        $user = Session::get('user');

        if (!in_array($user['role'] ?? '', $this->roles)) {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        return null;
    }
}

// routes.php
use PaigeJulianne\NanoMVC\{Router, ThrottleMiddleware, CsrfMiddleware};

// Public routes
Router::get('/', [HomeController::class, 'index']);
Router::get('/login', [AuthController::class, 'showLogin']);
Router::post('/login', [AuthController::class, 'login']);

// Protected routes
Router::group(['middleware' => [AuthMiddleware::class]], function() {

    // User routes
    Router::get('/dashboard', [DashboardController::class, 'index']);
    Router::get('/profile', [ProfileController::class, 'index']);

    // Form routes with CSRF
    Router::group(['middleware' => [new CsrfMiddleware()]], function() {
        Router::put('/profile', [ProfileController::class, 'update']);
        Router::post('/posts', [PostsController::class, 'store']);
    });

    // Admin routes
    Router::group([
        'prefix' => 'admin',
        'middleware' => [new RoleMiddleware(['admin'])]
    ], function() {
        Router::get('/', [AdminController::class, 'dashboard']);
        Router::get('/users', [AdminController::class, 'users']);
        Router::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    });
});

// API routes
Router::group(['prefix' => 'api', 'middleware' => [new CorsMiddleware()]], function() {

    // Public API
    Router::get('/status', fn() => ['status' => 'ok']);

    // Rate-limited API
    Router::group(['middleware' => [new ThrottleMiddleware(100, 1)]], function() {
        Router::get('/posts', [ApiController::class, 'posts']);
        Router::get('/posts/{id}', [ApiController::class, 'showPost']);
    });

    // Authenticated API
    Router::group(['middleware' => [AuthMiddleware::class]], function() {
        Router::post('/posts', [ApiController::class, 'createPost']);
        Router::put('/posts/{id}', [ApiController::class, 'updatePost']);
    });
});
```

---

## Next Steps

- [Routing](routing.md) - Route definitions
- [Security](security.md) - Security practices
- [Rate Limiting](rate-limiting.md) - Throttle configuration
