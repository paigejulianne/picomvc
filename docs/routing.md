# Routing

NanoMVC provides a powerful, high-performance routing system with support for parameters, groups, middleware, and route caching.

## Table of Contents

- [Basic Routing](#basic-routing)
- [Route Parameters](#route-parameters)
- [Route Groups](#route-groups)
- [Middleware](#middleware)
- [Route Handlers](#route-handlers)
- [Error Handling](#error-handling)
- [Route Caching](#route-caching)
- [Performance](#performance)

---

## Basic Routing

### HTTP Methods

```php
use PaigeJulianne\NanoMVC\Router;

Router::get('/path', $handler);      // GET requests
Router::post('/path', $handler);     // POST requests
Router::put('/path', $handler);      // PUT requests
Router::patch('/path', $handler);    // PATCH requests
Router::delete('/path', $handler);   // DELETE requests
Router::any('/path', $handler);      // All methods
Router::match(['GET', 'POST'], '/path', $handler);  // Specific methods
```

### Simple Examples

```php
// Return a string
Router::get('/', function() {
    return 'Hello, World!';
});

// Return JSON (arrays/objects auto-convert)
Router::get('/api/status', function() {
    return ['status' => 'ok', 'version' => '1.0.0'];
});

// Return a Response object
Router::get('/custom', function() {
    return Response::html('<h1>Custom</h1>', 200)
        ->header('X-Custom', 'value');
});
```

---

## Route Parameters

### Required Parameters

```php
// Single parameter
Router::get('/users/{id}', function(Request $request) {
    $id = $request->param('id');
    return "User ID: $id";
});

// Multiple parameters
Router::get('/posts/{year}/{month}/{slug}', function(Request $request) {
    $year = $request->param('year');
    $month = $request->param('month');
    $slug = $request->param('slug');

    return "Post: $year/$month/$slug";
});

// Get all parameters
Router::get('/articles/{category}/{id}', function(Request $request) {
    $params = $request->params();
    // ['category' => 'tech', 'id' => '123']

    return $params;
});
```

### Optional Parameters

```php
// Optional parameter (matches /users and /users/123)
Router::get('/users/{id?}', function(Request $request) {
    $id = $request->param('id');

    if ($id) {
        return "User ID: $id";
    }

    return "All users";
});
```

### Parameter Constraints

```php
// Numeric ID only
Router::get('/users/{id:\d+}', function(Request $request) {
    return "User ID: " . $request->param('id');
});

// Slug format
Router::get('/posts/{slug:[a-z0-9-]+}', function(Request $request) {
    return "Post: " . $request->param('slug');
});

// UUID format
Router::get('/items/{uuid:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}}', function(Request $request) {
    return "Item UUID: " . $request->param('uuid');
});

// Custom regex
Router::get('/files/{path:.+}', function(Request $request) {
    // Matches any path including slashes
    return "File path: " . $request->param('path');
});
```

---

## Route Groups

### Prefix Groups

```php
// All routes prefixed with /api
Router::group(['prefix' => 'api'], function() {
    Router::get('/users', [ApiController::class, 'users']);
    Router::get('/posts', [ApiController::class, 'posts']);
});
// Creates: /api/users, /api/posts
```

### Nested Groups

```php
Router::group(['prefix' => 'api'], function() {

    // v1 endpoints
    Router::group(['prefix' => 'v1'], function() {
        Router::get('/users', [ApiV1Controller::class, 'users']);
    });

    // v2 endpoints
    Router::group(['prefix' => 'v2'], function() {
        Router::get('/users', [ApiV2Controller::class, 'users']);
    });
});
// Creates: /api/v1/users, /api/v2/users
```

### Groups with Middleware

```php
Router::group(['prefix' => 'admin', 'middleware' => [AuthMiddleware::class]], function() {
    Router::get('/dashboard', [AdminController::class, 'dashboard']);
    Router::get('/users', [AdminController::class, 'users']);
    Router::get('/settings', [AdminController::class, 'settings']);
});
```

### Complex Group Example

```php
// Public API routes
Router::group(['prefix' => 'api'], function() {

    // Public endpoints
    Router::get('/status', [ApiController::class, 'status']);

    // Authenticated endpoints
    Router::group(['middleware' => [AuthMiddleware::class]], function() {

        Router::get('/me', [ApiController::class, 'me']);
        Router::put('/me', [ApiController::class, 'updateMe']);

        // Admin-only endpoints
        Router::group(['prefix' => 'admin', 'middleware' => [AdminMiddleware::class]], function() {
            Router::get('/users', [AdminApiController::class, 'users']);
            Router::delete('/users/{id}', [AdminApiController::class, 'deleteUser']);
        });
    });
});
```

---

## Middleware

### Inline Middleware

```php
Router::get('/dashboard', [DashboardController::class, 'index'], [
    function(Request $request) {
        if (!isset($_SESSION['user_id'])) {
            return Response::redirect('/login');
        }
        return null;  // Continue to handler
    }
]);
```

### Middleware Classes

```php
// middleware/AuthMiddleware.php
class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (!Session::has('user_id')) {
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Unauthorized'], 401);
            }
            return Response::redirect('/login');
        }

        return null;  // Continue to next middleware/handler
    }
}

// Usage
Router::get('/profile', [ProfileController::class, 'index'], [
    AuthMiddleware::class
]);
```

### Multiple Middleware

```php
Router::get('/admin/settings', [AdminController::class, 'settings'], [
    AuthMiddleware::class,
    AdminMiddleware::class,
    LoggingMiddleware::class
]);
```

### Built-in Middleware

NanoMVC includes several middleware classes:

```php
use PaigeJulianne\NanoMVC\{ThrottleMiddleware, CsrfMiddleware, CorsMiddleware};

// Rate limiting
Router::get('/api/data', $handler, [
    new ThrottleMiddleware(60, 1)  // 60 requests per minute
]);

// CSRF protection
Router::post('/form', $handler, [
    new CsrfMiddleware()
]);

// CORS
Router::group(['prefix' => 'api', 'middleware' => [new CorsMiddleware()]], function() {
    // API routes
});
```

---

## Route Handlers

### Closure Handlers

```php
Router::get('/hello/{name}', function(Request $request) {
    $name = $request->param('name');
    return "Hello, $name!";
});
```

### Controller Handlers

```php
// Array syntax [ControllerClass, 'method']
Router::get('/users', [UsersController::class, 'index']);
Router::get('/users/{id}', [UsersController::class, 'show']);
Router::post('/users', [UsersController::class, 'store']);
Router::put('/users/{id}', [UsersController::class, 'update']);
Router::delete('/users/{id}', [UsersController::class, 'destroy']);
```

### Return Types

```php
// String - returned as HTML
Router::get('/html', function() {
    return '<h1>Hello</h1>';
});

// Array/Object - returned as JSON
Router::get('/json', function() {
    return ['message' => 'Hello'];
});

// Response object - returned as-is
Router::get('/response', function() {
    return Response::json(['data' => 'value'])
        ->header('X-Custom', 'header');
});
```

---

## Error Handling

### 404 Not Found Handler

```php
use PaigeJulianne\NanoMVC\View;

Router::setNotFoundHandler(function(Request $request) {
    if ($request->expectsJson()) {
        return Response::json([
            'error' => 'Not Found',
            'message' => 'The requested resource was not found'
        ], 404);
    }

    return View::make('errors.404', [
        'path' => $request->path()
    ], 404);
});
```

### Error Handler

```php
Router::setErrorHandler(function(\Throwable $e, Request $request) {
    // Log the error
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());

    if ($request->expectsJson()) {
        $data = ['error' => 'Internal Server Error'];

        if (App::isDebug()) {
            $data['message'] = $e->getMessage();
            $data['trace'] = $e->getTraceAsString();
        }

        return Response::json($data, 500);
    }

    if (App::isDebug()) {
        return Response::html(
            '<h1>Error</h1><pre>' .
            htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString()) .
            '</pre>',
            500
        );
    }

    return View::make('errors.500', [], 500);
});
```

---

## Route Caching

For production applications with many routes, caching significantly improves performance.

### Generating Route Cache

```php
// cache-routes.php (run during deployment)
<?php
require 'vendor/autoload.php';

use PaigeJulianne\NanoMVC\Router;

// Load all routes
require 'routes.php';

// Generate cache file
Router::cacheRoutes(__DIR__ . '/storage/cache/routes.php');

echo "Routes cached successfully!\n";
```

### Loading Cached Routes

```php
// index.php
<?php
require 'vendor/autoload.php';

use PaigeJulianne\NanoMVC\{App, Router};

$cacheFile = __DIR__ . '/storage/cache/routes.php';

// Try to load cached routes in production
if (!App::isDebug() && file_exists($cacheFile)) {
    Router::loadCachedRoutes($cacheFile);
} else {
    require 'routes.php';
}

App::run(__DIR__);
```

### Cache Considerations

- Closure-based routes cannot be cached (use controller classes instead)
- Regenerate cache after route changes
- Cache file should be in `.gitignore`

```bash
# Deployment script
php cache-routes.php
```

---

## Performance

### Route Indexing

NanoMVC automatically indexes routes for O(1) lookup:

- **Static routes** (no parameters) use hash map for instant matching
- **Dynamic routes** are indexed by first path segment
- This means 500+ routes perform as well as 5 routes

### Monitoring Performance

```php
// Get route statistics
$stats = Router::getStats();

print_r($stats);
// [
//     'total_routes' => 150,
//     'static_routes' => 80,      // O(1) lookup
//     'dynamic_routes' => 70,     // Indexed by segment
//     'by_method' => ['GET' => 100, 'POST' => 50],
//     'cached_middleware' => 5,
//     'routes_cached' => true
// ]
```

### Best Practices

1. **Use static routes when possible** - They're matched instantly
2. **Use controller classes** - Required for route caching
3. **Cache routes in production** - Eliminates route compilation overhead
4. **Group related routes** - Reduces middleware instantiation
5. **Use route prefixes** - Improves indexing efficiency

```php
// Good: Static route (O(1) lookup)
Router::get('/about', [PageController::class, 'about']);

// Good: Indexed by 'users' segment
Router::get('/users/{id}', [UsersController::class, 'show']);

// Less optimal: Indexed under wildcard
Router::get('/{slug}', [PageController::class, 'show']);
```

---

## Complete Example

```php
<?php
// routes.php

use PaigeJulianne\NanoMVC\{Router, Response, View};

// Controllers
require_once 'controllers/HomeController.php';
require_once 'controllers/UsersController.php';
require_once 'controllers/AdminController.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'middleware/AdminMiddleware.php';

// Public routes
Router::get('/', [HomeController::class, 'index']);
Router::get('/about', [HomeController::class, 'about']);
Router::get('/contact', [HomeController::class, 'contact']);
Router::post('/contact', [HomeController::class, 'submitContact']);

// User routes
Router::get('/users', [UsersController::class, 'index']);
Router::get('/users/{id}', [UsersController::class, 'show']);

// Auth required routes
Router::group(['middleware' => [AuthMiddleware::class]], function() {
    Router::get('/profile', [UsersController::class, 'profile']);
    Router::put('/profile', [UsersController::class, 'updateProfile']);
    Router::get('/settings', [UsersController::class, 'settings']);
});

// Admin routes
Router::group([
    'prefix' => 'admin',
    'middleware' => [AuthMiddleware::class, AdminMiddleware::class]
], function() {
    Router::get('/', [AdminController::class, 'dashboard']);
    Router::get('/users', [AdminController::class, 'users']);
    Router::delete('/users/{id}', [AdminController::class, 'deleteUser']);
});

// API routes
Router::group(['prefix' => 'api', 'middleware' => [new CorsMiddleware()]], function() {

    // Public API
    Router::get('/status', fn() => ['status' => 'ok']);

    // Rate-limited API
    Router::group(['middleware' => [new ThrottleMiddleware(100, 1)]], function() {
        Router::get('/users', [UsersController::class, 'apiIndex']);
        Router::get('/users/{id}', [UsersController::class, 'apiShow']);
    });
});

// Error handlers
Router::setNotFoundHandler(function(Request $request) {
    return View::make('errors.404', [], 404);
});

Router::setErrorHandler(function(\Throwable $e, Request $request) {
    return View::make('errors.500', ['error' => $e], 500);
});
```

---

## Next Steps

- [Controllers](controllers.md) - Learn about controllers
- [Middleware](middleware.md) - Middleware patterns
- [Performance](performance.md) - Optimization techniques
