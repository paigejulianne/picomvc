# API Reference

Complete reference for all NanoMVC classes and methods.

## Table of Contents

- [App](#app)
- [Router](#router)
- [Controller](#controller)
- [Request](#request)
- [Response](#response)
- [View](#view)
- [Session](#session)
- [Validator](#validator)
- [RateLimiter](#ratelimiter)
- [Middleware Classes](#middleware-classes)

---

## App

Main application class.

```php
use PaigeJulianne\NanoMVC\App;
```

### Methods

| Method | Description |
|--------|-------------|
| `App::run(string $basePath)` | Run the application |
| `App::loadConfig(string $path)` | Load configuration from file |
| `App::config(string $key, mixed $default = null)` | Get configuration value |
| `App::isDebug(): bool` | Check if debug mode is enabled |
| `App::getBasePath(): string` | Get application base path |

### Example

```php
App::loadConfig(__DIR__ . '/.config');
$debug = App::config('app.debug', false);
$dbHost = App::config('database.host', 'localhost');

if (App::isDebug()) {
    // Development mode
}

App::run(__DIR__);
```

---

## Router

HTTP routing system.

```php
use PaigeJulianne\NanoMVC\Router;
```

### Route Methods

| Method | Description |
|--------|-------------|
| `Router::get(string $path, $handler, array $middleware = [])` | Register GET route |
| `Router::post(string $path, $handler, array $middleware = [])` | Register POST route |
| `Router::put(string $path, $handler, array $middleware = [])` | Register PUT route |
| `Router::patch(string $path, $handler, array $middleware = [])` | Register PATCH route |
| `Router::delete(string $path, $handler, array $middleware = [])` | Register DELETE route |
| `Router::any(string $path, $handler, array $middleware = [])` | Register route for all methods |
| `Router::match(array $methods, string $path, $handler, array $middleware = [])` | Register route for specific methods |
| `Router::group(array $options, callable $callback)` | Group routes with shared settings |

### Configuration Methods

| Method | Description |
|--------|-------------|
| `Router::setNotFoundHandler(callable $handler)` | Set 404 handler |
| `Router::setErrorHandler(callable $handler)` | Set error handler |
| `Router::cacheRoutes(string $path)` | Cache routes to file |
| `Router::loadCachedRoutes(string $path): bool` | Load cached routes |
| `Router::getStats(): array` | Get routing statistics |
| `Router::clearMiddlewareCache()` | Clear middleware cache |

### Example

```php
Router::get('/users', [UsersController::class, 'index']);
Router::get('/users/{id}', [UsersController::class, 'show']);
Router::post('/users', [UsersController::class, 'store'], [AuthMiddleware::class]);

Router::group(['prefix' => 'api', 'middleware' => [CorsMiddleware::class]], function() {
    Router::get('/status', fn() => ['status' => 'ok']);
});

Router::setNotFoundHandler(function(Request $request) {
    return Response::json(['error' => 'Not found'], 404);
});
```

---

## Controller

Base controller class.

```php
use PaigeJulianne\NanoMVC\Controller;
```

### Response Methods

| Method | Description |
|--------|-------------|
| `$this->view(string $name, array $data = [], int $status = 200): Response` | Render view |
| `$this->json(mixed $data, int $status = 200): Response` | Return JSON |
| `$this->html(string $content, int $status = 200): Response` | Return HTML |
| `$this->text(string $content, int $status = 200): Response` | Return plain text |
| `$this->redirect(string $url, int $status = 302): Response` | Redirect |

### Validation Method

| Method | Description |
|--------|-------------|
| `$this->validate(array $rules): array` | Validate request input |

### Example

```php
class UsersController extends Controller
{
    public function show(Request $request): Response
    {
        $id = $request->param('id');
        $user = User::find($id);

        if (!$user) {
            return $this->json(['error' => 'Not found'], 404);
        }

        return $this->view('users.show', ['user' => $user]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email'
        ]);

        $user = User::create($data);
        return $this->json($user, 201);
    }
}
```

---

## Request

HTTP request wrapper.

```php
use PaigeJulianne\NanoMVC\Request;
```

### Input Methods

| Method | Description |
|--------|-------------|
| `$request->input(string $key, mixed $default = null)` | Get POST input |
| `$request->query(string $key, mixed $default = null)` | Get GET parameter |
| `$request->all(): array` | Get all input (GET + POST) |
| `$request->only(array $keys): array` | Get only specific keys |
| `$request->except(array $keys): array` | Get all except keys |
| `$request->has(string $key): bool` | Check if key exists |
| `$request->filled(string $key): bool` | Check if key exists and not empty |
| `$request->json(string $key = null)` | Get JSON body or key |

### Route Parameter Methods

| Method | Description |
|--------|-------------|
| `$request->param(string $key, mixed $default = null)` | Get route parameter |
| `$request->params(): array` | Get all route parameters |

### Request Info Methods

| Method | Description |
|--------|-------------|
| `$request->method(): string` | Get HTTP method |
| `$request->path(): string` | Get request path |
| `$request->url(): string` | Get full URL |
| `$request->isAjax(): bool` | Check if AJAX request |
| `$request->expectsJson(): bool` | Check if expects JSON |

### Header/Cookie Methods

| Method | Description |
|--------|-------------|
| `$request->header(string $key, mixed $default = null)` | Get header |
| `$request->headers(): array` | Get all headers |
| `$request->cookie(string $key, mixed $default = null)` | Get cookie |

### File Methods

| Method | Description |
|--------|-------------|
| `$request->file(string $key): ?array` | Get uploaded file |
| `$request->getContent(): string` | Get raw body |
| `$request->getContentStream()` | Get body as stream |
| `$request->readContentChunked(callable $callback, int $chunkSize = 8192)` | Read body in chunks |

### Static Methods

| Method | Description |
|--------|-------------|
| `Request::setMaxBodySize(int $bytes)` | Set max body size |
| `Request::getMaxBodySize(): int` | Get max body size |

---

## Response

HTTP response builder.

```php
use PaigeJulianne\NanoMVC\Response;
```

### Static Factory Methods

| Method | Description |
|--------|-------------|
| `Response::html(string $content, int $status = 200): Response` | Create HTML response |
| `Response::json(mixed $data, int $status = 200): Response` | Create JSON response |
| `Response::text(string $content, int $status = 200): Response` | Create text response |
| `Response::redirect(string $url, int $status = 302): Response` | Create redirect |

### Instance Methods

| Method | Description |
|--------|-------------|
| `$response->setContent(string $content): self` | Set body content |
| `$response->setStatusCode(int $code): self` | Set status code |
| `$response->getStatusCode(): int` | Get status code |
| `$response->header(string $name, string $value): self` | Add header |
| `$response->headers(array $headers): self` | Add multiple headers |
| `$response->cookie(string $name, string $value, array $options = []): self` | Set cookie |

### Compression Methods

| Method | Description |
|--------|-------------|
| `Response::configureCompression(int $threshold, int $level)` | Configure compression |
| `$response->withCompression(): self` | Enable compression |
| `$response->withoutCompression(): self` | Disable compression |

### Example

```php
return Response::json(['status' => 'ok'])
    ->header('X-Request-Id', $id)
    ->header('Cache-Control', 'no-cache');

return (new Response())
    ->setContent($pdfContent)
    ->setStatusCode(200)
    ->header('Content-Type', 'application/pdf')
    ->header('Content-Disposition', 'attachment; filename="doc.pdf"');
```

---

## View

Template rendering.

```php
use PaigeJulianne\NanoMVC\View;
```

### Methods

| Method | Description |
|--------|-------------|
| `View::configure(string $viewsPath, string $cachePath, string $engine)` | Configure view system |
| `View::make(string $name, array $data = [], int $status = 200): Response` | Render view |
| `View::share(string $key, mixed $value)` | Share data globally |
| `View::getTemplateAdapter()` | Get template engine adapter |

### Example

```php
View::configure(
    viewsPath: __DIR__ . '/views',
    cachePath: __DIR__ . '/cache',
    engine: 'blade'
);

View::share('appName', 'My App');

return View::make('users.index', ['users' => $users]);
```

---

## Session

Session management.

```php
use PaigeJulianne\NanoMVC\Session;
```

### Configuration

| Method | Description |
|--------|-------------|
| `Session::configure(array $options)` | Configure session |
| `Session::setDriver(SessionDriver $driver)` | Set custom driver |

### Basic Operations

| Method | Description |
|--------|-------------|
| `Session::start()` | Start session |
| `Session::isStarted(): bool` | Check if started |
| `Session::getId(): string` | Get session ID |
| `Session::setId(string $id)` | Set session ID |
| `Session::regenerate()` | Regenerate session ID |
| `Session::destroy()` | Destroy session |

### Data Operations

| Method | Description |
|--------|-------------|
| `Session::get(string $key, mixed $default = null)` | Get value |
| `Session::set(string $key, mixed $value)` | Set value |
| `Session::has(string $key): bool` | Check if key exists |
| `Session::forget(string $key)` | Remove key |
| `Session::flush()` | Clear all data |
| `Session::all(): array` | Get all data |

### Flash Messages

| Method | Description |
|--------|-------------|
| `Session::flash(string $key, mixed $value)` | Set flash message |
| `Session::getFlash(string $key, mixed $default = null)` | Get and remove flash |

### CSRF

| Method | Description |
|--------|-------------|
| `Session::csrfToken(): string` | Get/create CSRF token |
| `Session::verifyCsrfToken(string $token): bool` | Verify CSRF token |

### Example

```php
Session::configure([
    'name' => 'myapp',
    'lifetime' => 7200,
    'secure' => true
]);

Session::set('user_id', 123);
$userId = Session::get('user_id');

Session::flash('success', 'Saved!');
$message = Session::getFlash('success');

$token = Session::csrfToken();
```

---

## Validator

Input validation.

```php
use PaigeJulianne\NanoMVC\Validator;
use PaigeJulianne\NanoMVC\ValidationException;
```

### Validator Class

| Method | Description |
|--------|-------------|
| `new Validator(array $data, array $rules)` | Create validator |
| `$validator->fails(): bool` | Check if validation failed |
| `$validator->passes(): bool` | Check if validation passed |
| `$validator->errors(): array` | Get all errors |
| `$validator->validated(): array` | Get validated data |

### ValidationException

| Method | Description |
|--------|-------------|
| `$e->getErrors(): array` | Get validation errors |
| `$e->toResponse(): Response` | Convert to JSON response |

### Available Rules

| Rule | Description |
|------|-------------|
| `required` | Field must exist and not be empty |
| `email` | Valid email format |
| `url` | Valid URL format |
| `numeric` | Numeric value |
| `integer` | Integer value |
| `min:n` | Minimum length or value |
| `max:n` | Maximum length or value |
| `in:a,b,c` | Must be in list |
| `alpha` | Letters only |
| `alphanumeric` | Letters and numbers only |

### Example

```php
$validator = new Validator($request->all(), [
    'name' => 'required|min:2|max:100',
    'email' => 'required|email',
    'age' => 'integer|min:18'
]);

if ($validator->fails()) {
    return Response::json(['errors' => $validator->errors()], 422);
}

$data = $validator->validated();
```

---

## RateLimiter

Rate limiting system.

```php
use PaigeJulianne\NanoMVC\RateLimiter;
use PaigeJulianne\NanoMVC\FileRateLimitStore;
```

### RateLimiter Methods

| Method | Description |
|--------|-------------|
| `new RateLimiter(RateLimitStore $store)` | Create limiter |
| `$limiter->attempt(string $key, int $maxAttempts, int $decayMinutes): bool` | Check/record attempt |
| `$limiter->tooManyAttempts(string $key, int $maxAttempts): bool` | Check if exceeded |
| `$limiter->remaining(string $key, int $maxAttempts): int` | Get remaining attempts |
| `$limiter->availableIn(string $key): int` | Seconds until available |
| `$limiter->clear(string $key)` | Clear attempts |

### Example

```php
$store = new FileRateLimitStore('/tmp/rate-limits');
$limiter = new RateLimiter($store);

$key = 'login:' . $request->input('email');

if (!$limiter->attempt($key, 5, 1)) {
    return Response::json([
        'error' => 'Too many attempts',
        'retry_after' => $limiter->availableIn($key)
    ], 429);
}
```

---

## Middleware Classes

### ThrottleMiddleware

```php
use PaigeJulianne\NanoMVC\ThrottleMiddleware;

// 60 requests per minute
new ThrottleMiddleware(60, 1);

// 100 requests per hour
new ThrottleMiddleware(100, 60);
```

### CsrfMiddleware

```php
use PaigeJulianne\NanoMVC\CsrfMiddleware;

// Protect all routes
new CsrfMiddleware();

// Exclude paths
new CsrfMiddleware(['/api/*', '/webhooks/*']);
```

### CorsMiddleware

```php
use PaigeJulianne\NanoMVC\CorsMiddleware;

// Default (allow all)
new CorsMiddleware();

// Configured
new CorsMiddleware([
    'allowedOrigins' => ['https://example.com'],
    'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowedHeaders' => ['Content-Type', 'Authorization'],
    'allowCredentials' => true,
    'maxAge' => 86400
]);
```

### Custom Middleware

```php
class MyMiddleware
{
    public function handle(Request $request): ?Response
    {
        // Return null to continue
        // Return Response to stop
    }
}
```

---

## Next Steps

- [Quick Start](quick-start.md) - Get started
- [Routing](routing.md) - Route definitions
- [Controllers](controllers.md) - Controller patterns
