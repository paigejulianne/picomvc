# NanoMVC

A lightweight MVC framework for PHP 8.0+ with support for Blade and Smarty templates.

**Version 1.0.0** | [Changelog](CHANGELOG.md) | [License: GPL-3.0](LICENSE)

by Paige Julianne Sullivan
[paigejulianne.com](https://paigejulianne.com) | [GitHub](https://github.com/paigejulianne/nanomvc)

---

## Features

- **Enterprise-scale performance**: O(1) route lookup, route caching, middleware caching
- **Multiple template engines**: PHP, Blade, and Smarty support
- **Simple routing**: Clean URL routing with parameters and groups
- **Zero dependencies**: Only requires PHP 8.0+ (template engines optional)
- **Built-in validation**: Request validation with helpful error messages
- **Session management**: Secure sessions with CSRF protection
- **Rate limiting**: Configurable throttling for API endpoints
- **Response compression**: Automatic gzip compression
- **Integrates with NanoORM**: Seamlessly works with [NanoORM](https://github.com/paigejulianne/nanoorm) for database operations

---

## Prerequisites

- **PHP 8.0+** with the following extensions:
  - `json` (usually enabled by default)
  - `mbstring` (recommended)
- **Apache** with `mod_rewrite` enabled, or **Nginx**
- **Composer** (for installation and autoloading)

---

## Installation

### Via Composer (Recommended)

```bash
composer require paigejulianne/nanomvc
```

### Manual Installation

Download `NanoMVC.php` and include it in your project:

```php
require_once 'NanoMVC.php';
```

---

## Examples

NanoMVC includes three example applications demonstrating each template engine:

| Example | Template Engine | Directory |
|---------|-----------------|-----------|
| PHP (Native) | Native PHP | `example/` |
| Blade | Laravel Blade | `example-blade/` |
| Smarty | Smarty | `example-smarty/` |

### Running the Examples

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **For Blade example, also install:**
   ```bash
   composer require jenssegers/blade
   ```

3. **For Smarty example, also install:**
   ```bash
   composer require smarty/smarty
   ```

4. **Set file permissions:**
   ```bash
   chmod 644 example*/.htaccess
   chmod 755 example*/cache
   ```

5. **Access in your browser:**
   - PHP: `http://localhost/path/to/nanomvc/example/`
   - Blade: `http://localhost/path/to/nanomvc/example-blade/`
   - Smarty: `http://localhost/path/to/nanomvc/example-smarty/`

> **Note:** Ensure Apache `mod_rewrite` is enabled and `AllowOverride All` is set for your directory. See [Apache Configuration](#apache-configuration) for details.

---

## Quick Start

### 1. Create Your Entry Point

Create `index.php`:

```php
<?php
require_once 'vendor/autoload.php';

use PaigeJulianne\NanoMVC\App;

App::run(__DIR__);
```

### 2. Create Configuration (Optional)

Create `.config`:

```ini
[app]
debug=true

[views]
engine=php
path=views
cache=cache
```

### 3. Define Routes

Create `routes.php`:

```php
<?php
use PaigeJulianne\NanoMVC\Router;

Router::get('/', [HomeController::class, 'index']);
Router::get('/users/{id}', [UsersController::class, 'show']);
Router::post('/users', [UsersController::class, 'store']);
```

### 4. Create a Controller

Create `controllers/HomeController.php`:

```php
<?php
use PaigeJulianne\NanoMVC\Controller;
use PaigeJulianne\NanoMVC\Request;
use PaigeJulianne\NanoMVC\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('home', [
            'title' => 'Welcome',
            'message' => 'Hello, World!',
        ]);
    }
}
```

### 5. Create a View

Create `views/home.php`:

```php
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title) ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($message) ?></h1>
</body>
</html>
```

---

## Configuration

### Using a `.config` File

```ini
[app]
debug=true
name=My Application

[views]
engine=php      # php, blade, or smarty
path=views
cache=cache

[routes]
file=routes.php
```

### Programmatic Configuration

```php
use PaigeJulianne\NanoMVC\App;
use PaigeJulianne\NanoMVC\View;

App::setDebug(true);
App::setConfig('app.name', 'My App');
View::configure('/path/to/views', '/path/to/cache', 'blade');
```

---

## Routing

### Basic Routes

```php
use PaigeJulianne\NanoMVC\Router;

Router::get('/path', $handler);
Router::post('/path', $handler);
Router::put('/path', $handler);
Router::patch('/path', $handler);
Router::delete('/path', $handler);
Router::any('/path', $handler);      // Matches any method
```

### Route Parameters

```php
// Required parameter
Router::get('/users/{id}', function (Request $request) {
    $id = $request->param('id');
    return "User ID: $id";
});

// Multiple parameters
Router::get('/posts/{year}/{month}/{slug}', function (Request $request) {
    return $request->params(); // ['year' => '2024', 'month' => '12', 'slug' => 'hello']
});
```

### Route Groups

```php
Router::group(['prefix' => 'api'], function () {
    Router::get('/users', [ApiController::class, 'users']);
    Router::get('/posts', [ApiController::class, 'posts']);
});
// Creates: /api/users and /api/posts
```

### Nested Groups

```php
Router::group(['prefix' => 'api'], function () {
    Router::group(['prefix' => 'v1'], function () {
        Router::get('/users', [ApiV1Controller::class, 'users']);
    });
});
// Creates: /api/v1/users
```

### Route Handlers

Routes can use closures or controller methods:

```php
// Closure
Router::get('/hello', function (Request $request) {
    return 'Hello, World!';
});

// Controller method [ClassName, 'methodName']
Router::get('/users', [UsersController::class, 'index']);
```

### Middleware

```php
// Inline middleware
Router::get('/admin', [AdminController::class, 'index'], [
    function (Request $request) {
        if (!isLoggedIn()) {
            return Response::redirect('/login');
        }
        return null; // Continue to handler
    }
]);

// Middleware class
Router::get('/dashboard', [DashboardController::class, 'index'], [
    AuthMiddleware::class
]);
```

### Custom Error Handlers

```php
// Custom 404 handler
Router::setNotFoundHandler(function (Request $request) {
    return View::make('errors.404', [], 404);
});

// Custom error handler
Router::setErrorHandler(function (\Throwable $e, Request $request) {
    return View::make('errors.500', ['error' => $e->getMessage()], 500);
});
```

### Route Caching (Production)

For large applications, cache compiled routes for faster startup:

```php
// Generate route cache (run during deployment)
require 'routes.php';
Router::cacheRoutes('/path/to/cache/routes.php');

// Load cached routes in production
if (file_exists('/path/to/cache/routes.php')) {
    Router::loadCachedRoutes('/path/to/cache/routes.php');
} else {
    require 'routes.php';
}
```

### Route Statistics

Monitor route performance:

```php
$stats = Router::getStats();
// Returns: [
//   'total_routes' => 150,
//   'static_routes' => 80,
//   'dynamic_routes' => 70,
//   'by_method' => ['GET' => 100, 'POST' => 50],
//   'cached_middleware' => 5,
//   'routes_cached' => true
// ]
```

---

## Controllers

### Creating Controllers

Extend the base `Controller` class:

```php
use PaigeJulianne\NanoMVC\Controller;
use PaigeJulianne\NanoMVC\Request;
use PaigeJulianne\NanoMVC\Response;

class UsersController extends Controller
{
    public function index(Request $request): Response
    {
        $users = Users::getAllObjects();
        return $this->view('users.index', ['users' => $users]);
    }

    public function show(Request $request): Response
    {
        $id = $request->param('id');
        $user = new Users($id);
        return $this->view('users.show', ['user' => $user]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate([
            'name' => 'required|min:2|max:100',
            'email' => 'required|email',
        ]);

        $user = new Users();
        $user->setMulti($data);
        $user->save();

        return $this->redirect('/users/' . $user->getId());
    }
}
```

### Response Methods

```php
// Render a view
$this->view('template', ['data' => 'value'], 200);

// JSON response
$this->json(['key' => 'value'], 200);

// Redirect
$this->redirect('/path', 302);

// Plain text
$this->text('Plain text', 200);

// HTML
$this->html('<h1>HTML</h1>', 200);
```

### Validation

```php
$data = $this->validate([
    'name' => 'required|min:2|max:100',
    'email' => 'required|email',
    'age' => 'numeric|min:1',
    'role' => 'in:admin,user,guest',
]);
```

**Available Rules:**
- `required` - Field must be present and not empty
- `email` - Must be a valid email address
- `numeric` - Must be numeric
- `integer` - Must be an integer
- `min:n` - Minimum string length
- `max:n` - Maximum string length
- `in:a,b,c` - Must be one of the specified values
- `url` - Must be a valid URL
- `alpha` - Must contain only letters
- `alphanumeric` - Must contain only letters and numbers

---

## Request Object

### Getting Input

```php
// Query parameters (?foo=bar)
$request->query('foo');
$request->query('foo', 'default');
$request->allQuery();

// POST data
$request->input('field');
$request->input('field', 'default');

// All input (POST + GET)
$request->all();
$request->only(['field1', 'field2']);
$request->except(['password']);

// Check if exists
$request->has('field');
```

### Route Parameters

```php
$request->param('id');
$request->param('id', 'default');
$request->params(); // All route params
```

### Request Info

```php
$request->method();      // GET, POST, etc.
$request->path();        // /users/123
$request->header('Accept');
$request->cookie('session');
$request->isAjax();
$request->expectsJson();
$request->getContent();  // Raw body
$request->json();        // JSON decoded body
```

---

## Response Object

### Creating Responses

```php
use PaigeJulianne\NanoMVC\Response;

// JSON
$response = Response::json(['data' => 'value'], 200);

// Redirect
$response = Response::redirect('/path', 302);

// Plain text
$response = Response::text('Content', 200);

// HTML
$response = Response::html('<h1>Hello</h1>', 200);
```

### Modifying Responses

```php
$response = new Response();
$response->setContent('Hello')
         ->setStatusCode(200)
         ->header('X-Custom', 'value')
         ->withHeaders(['X-A' => '1', 'X-B' => '2']);
```

### Response Compression

Responses are automatically gzip-compressed when the client supports it:

```php
// Configure compression (optional)
Response::configureCompression(
    threshold: 1024,  // Min bytes to compress (default 1KB)
    level: 6          // Compression level 0-9 (default 6)
);

// Disable compression for specific response
return Response::json($data)->withoutCompression();

// Re-enable compression
return Response::html($content)->withCompression();
```

---

## Views

### Template Engines

**PHP (Native)** - Default, no dependencies:

```php
View::configure('/path/to/views', '/path/to/cache', 'php');
```

**Blade** - Requires `jenssegers/blade`:

```bash
composer require jenssegers/blade
```

```php
View::configure('/path/to/views', '/path/to/cache', 'blade');
```

**Smarty** - Requires `smarty/smarty`:

```bash
composer require smarty/smarty
```

```php
View::configure('/path/to/views', '/path/to/cache', 'smarty');
```

### Rendering Views

```php
// In controller
return $this->view('users.index', ['users' => $users]);

// Directly
$html = View::render('template', ['data' => 'value']);

// As response
$response = View::make('template', ['data' => 'value'], 200);
```

### Shared Data

```php
// Share with all views
View::share('appName', 'My App');
View::share(['key1' => 'value1', 'key2' => 'value2']);
```

### Template Examples

**PHP Template (`views/users/index.php`):**

```php
<h1><?= htmlspecialchars($title) ?></h1>
<ul>
<?php foreach ($users as $user): ?>
    <li><?= htmlspecialchars($user->name) ?></li>
<?php endforeach; ?>
</ul>
```

**Blade Template (`views/users/index.blade.php`):**

```blade
<h1>{{ $title }}</h1>
<ul>
@foreach ($users as $user)
    <li>{{ $user->name }}</li>
@endforeach
</ul>
```

**Smarty Template (`views/users/index.tpl`):**

```smarty
<h1>{$title}</h1>
<ul>
{foreach $users as $user}
    <li>{$user->name}</li>
{/foreach}
</ul>
```

---

## Integration with NanoORM

### Creating Models

```php
use PaigeJulianne\NanoORM;

class Users extends NanoORM
{
    // Maps to 'users' table automatically
}

class BlogPost extends NanoORM
{
    const TABLE_OVERRIDE = 'blog_posts';
}
```

### Using Models in Controllers

```php
class UsersController extends Controller
{
    public function index(Request $request): Response
    {
        $users = Users::getAllObjects();
        return $this->view('users.index', ['users' => $users]);
    }

    public function show(Request $request): Response
    {
        $user = new Users($request->param('id'));
        return $this->view('users.show', ['user' => $user]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate([
            'name' => 'required',
            'email' => 'required|email',
        ]);

        $user = new Users();
        $user->setMulti($data);
        $user->save();

        return $this->redirect('/users/' . $user->getId());
    }

    public function destroy(Request $request): Response
    {
        $user = new Users($request->param('id'));
        $user->delete();

        return $this->redirect('/users');
    }
}
```

---

## Session Management

### Basic Usage

```php
use PaigeJulianne\NanoMVC\Session;

// Set and get values
Session::set('user_id', 123);
$userId = Session::get('user_id');
$name = Session::get('name', 'Guest');  // With default

// Check and remove
if (Session::has('user_id')) {
    Session::forget('user_id');
}

// Get all session data
$all = Session::all();

// Clear all data
Session::flush();

// Destroy session completely
Session::destroy();
```

### Flash Messages

```php
// Set flash message (available only on next request)
Session::flash('success', 'User created successfully!');

// Get flash message (automatically removed)
$message = Session::getFlash('success');
```

### CSRF Protection

```php
// Get CSRF token (for forms)
$token = Session::csrfToken();

// In your form
<input type="hidden" name="_token" value="<?= Session::csrfToken() ?>">

// Verify token (done automatically by CsrfMiddleware)
if (Session::verifyCsrfToken($request->input('_token'))) {
    // Valid token
}
```

### Session Configuration

```php
Session::configure([
    'name' => 'my_app_session',
    'lifetime' => 7200,      // 2 hours
    'path' => '/',
    'domain' => '',
    'secure' => true,        // HTTPS only
    'httponly' => true,      // No JavaScript access
    'samesite' => 'Lax',     // CSRF protection
]);
```

### Custom Session Storage

```php
use PaigeJulianne\NanoMVC\FileSessionDriver;

// Use file-based sessions with custom path
$driver = new FileSessionDriver('/path/to/sessions', 7200);
Session::setDriver($driver);
```

---

## Rate Limiting

### Basic Usage

```php
use PaigeJulianne\NanoMVC\RateLimiter;
use PaigeJulianne\NanoMVC\FileRateLimitStore;

// Configure storage (required for production)
RateLimiter::setStore(new FileRateLimitStore('/path/to/storage'));

// Check rate limit
$key = 'api:' . $userId;
if (RateLimiter::attempt($key, maxAttempts: 60, decaySeconds: 60)) {
    // Within limit - process request
} else {
    // Rate limited
    $retryAfter = RateLimiter::availableIn($key);
}

// Get remaining attempts
$remaining = RateLimiter::remaining($key, 60);

// Clear rate limit
RateLimiter::clear($key);
```

### Throttle Middleware

```php
use PaigeJulianne\NanoMVC\ThrottleMiddleware;

// 60 requests per minute
Router::get('/api/users', [ApiController::class, 'index'], [
    new ThrottleMiddleware(60, 1)
]);

// 100 requests per hour with custom key
Router::post('/api/search', [ApiController::class, 'search'], [
    new ThrottleMiddleware(100, 60, fn($req) => $req->header('X-API-Key'))
]);

// Apply to route group
Router::group(['prefix' => 'api', 'middleware' => [new ThrottleMiddleware(120, 1)]], function () {
    Router::get('/users', [ApiController::class, 'users']);
    Router::get('/posts', [ApiController::class, 'posts']);
});
```

---

## Security Middleware

### CSRF Middleware

```php
use PaigeJulianne\NanoMVC\CsrfMiddleware;

// Apply to all POST/PUT/DELETE routes
Router::group(['middleware' => [new CsrfMiddleware()]], function () {
    Router::post('/users', [UsersController::class, 'store']);
    Router::delete('/users/{id}', [UsersController::class, 'destroy']);
});

// Exclude specific paths (e.g., webhooks)
$csrf = new CsrfMiddleware(['/api/webhooks/*', '/api/stripe/*']);
```

### CORS Middleware

```php
use PaigeJulianne\NanoMVC\CorsMiddleware;

// Allow all origins
Router::group(['prefix' => 'api', 'middleware' => [new CorsMiddleware()]], function () {
    Router::get('/users', [ApiController::class, 'users']);
});

// Configure specific origins
$cors = new CorsMiddleware([
    'allowed_origins' => ['https://example.com', 'https://app.example.com'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-API-Key'],
    'exposed_headers' => ['X-RateLimit-Remaining'],
    'max_age' => 86400,
    'supports_credentials' => true,
]);
```

---

## Directory Structure

```
myapp/
├── .config              # Configuration
├── .htaccess            # Apache rewrite rules
├── index.php            # Entry point
├── routes.php           # Route definitions
├── controllers/
│   ├── HomeController.php
│   └── UsersController.php
├── models/
│   └── Users.php
├── views/
│   ├── layout.php
│   ├── home.php
│   └── users/
│       ├── index.php
│       └── show.php
└── cache/               # Template cache
```

---

## Apache Configuration

### Basic .htaccess

Create `.htaccess` in your application directory:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule .* index.php [L]
</IfModule>
```

> **Note:** Do not use `RewriteBase` unless your application is installed at the web root. The above configuration works for subdirectory installations.

### File Permissions

Ensure Apache can read the `.htaccess` file:

```bash
chmod 644 .htaccess
```

### Enabling mod_rewrite

If mod_rewrite is not enabled, run:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Apache User Directories (~username)

If running NanoMVC in a user directory (e.g., `http://localhost/~username/myapp/`), you need to configure Apache to allow `.htaccess` overrides.

Edit `/etc/apache2/mods-available/userdir.conf`:

```apache
<Directory /home/*/public_html>
    AllowOverride All
    Options All
    Require all granted
</Directory>
```

Then restart Apache:

```bash
sudo systemctl restart apache2
```

### Subdirectory Installation

NanoMVC automatically detects when installed in a subdirectory and adjusts routing accordingly. For links in your views, calculate the base URL:

```php
<?php $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<a href="<?= $baseUrl ?>/about">About</a>
```

---

## Nginx Configuration

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

---

## Testing

Run tests with PHPUnit:

```bash
composer test
```

---

## API Reference

### Router

| Method | Description |
|--------|-------------|
| `get($path, $handler, $middleware)` | Register GET route |
| `post($path, $handler, $middleware)` | Register POST route |
| `put($path, $handler, $middleware)` | Register PUT route |
| `patch($path, $handler, $middleware)` | Register PATCH route |
| `delete($path, $handler, $middleware)` | Register DELETE route |
| `any($path, $handler, $middleware)` | Register route for all methods |
| `match($methods, $path, $handler, $middleware)` | Register route for specific methods |
| `group($options, $callback)` | Create route group |
| `dispatch($request)` | Dispatch request to handler |
| `setNotFoundHandler($handler)` | Set 404 handler |
| `setErrorHandler($handler)` | Set error handler |

### Controller

| Method | Description |
|--------|-------------|
| `view($template, $data, $status)` | Render view response |
| `json($data, $status)` | JSON response |
| `redirect($url, $status)` | Redirect response |
| `text($content, $status)` | Plain text response |
| `html($content, $status)` | HTML response |
| `validate($rules)` | Validate request input |
| `request()` | Get current request |

### Request

| Method | Description |
|--------|-------------|
| `method()` | Get HTTP method |
| `path()` | Get request path |
| `query($key, $default)` | Get query parameter |
| `input($key, $default)` | Get input value |
| `all()` | Get all input |
| `only($keys)` | Get specific keys |
| `except($keys)` | Get all except keys |
| `has($key)` | Check if key exists |
| `param($name, $default)` | Get route parameter |
| `params()` | Get all route parameters |
| `header($name, $default)` | Get header |
| `cookie($name, $default)` | Get cookie |
| `isAjax()` | Check if AJAX request |
| `expectsJson()` | Check if expects JSON |
| `json()` | Get JSON body |

### Response

| Method | Description |
|--------|-------------|
| `setContent($content)` | Set response body |
| `setStatusCode($code)` | Set HTTP status |
| `header($name, $value)` | Add header |
| `withHeaders($headers)` | Add multiple headers |
| `send()` | Send response |
| `json($data, $status)` | Create JSON response |
| `redirect($url, $status)` | Create redirect response |
| `text($content, $status)` | Create text response |
| `html($content, $status)` | Create HTML response |

### View

| Method | Description |
|--------|-------------|
| `configure($viewsPath, $cachePath, $engine)` | Configure view system |
| `render($template, $data)` | Render template to string |
| `make($template, $data, $status)` | Create response with view |
| `share($key, $value)` | Share data with all views |
| `engineAvailable($engine)` | Check if engine available |

### App

| Method | Description |
|--------|-------------|
| `run($basePath)` | Run the application |
| `config($key, $default)` | Get config value |
| `setConfig($key, $value)` | Set config value |
| `isDebug()` | Check debug mode |
| `setDebug($debug)` | Set debug mode |
| `basePath($path)` | Get base path |

### Session

| Method | Description |
|--------|-------------|
| `start()` | Start the session |
| `get($key, $default)` | Get session value |
| `set($key, $value)` | Set session value |
| `has($key)` | Check if key exists |
| `forget($key)` | Remove session value |
| `all()` | Get all session data |
| `flush()` | Clear all session data |
| `destroy()` | Destroy session completely |
| `flash($key, $value)` | Flash value for next request |
| `getFlash($key, $default)` | Get and remove flash value |
| `csrfToken()` | Get CSRF token |
| `verifyCsrfToken($token)` | Verify CSRF token |
| `configure($config)` | Configure session settings |
| `setDriver($driver)` | Set custom session driver |
| `regenerate()` | Regenerate session ID |

### RateLimiter

| Method | Description |
|--------|-------------|
| `attempt($key, $max, $decay)` | Check/increment rate limit |
| `remaining($key, $max)` | Get remaining attempts |
| `availableIn($key)` | Seconds until limit resets |
| `clear($key)` | Clear rate limit for key |
| `hits($key)` | Get current hit count |
| `setStore($store)` | Set storage backend |

### Router (Additional Methods)

| Method | Description |
|--------|-------------|
| `cacheRoutes($file)` | Cache routes to file |
| `loadCachedRoutes($file)` | Load routes from cache |
| `isRouteCached()` | Check if routes are cached |
| `getStats()` | Get route statistics |
| `clearMiddlewareCache()` | Clear middleware cache |

### Response (Additional Methods)

| Method | Description |
|--------|-------------|
| `withCompression()` | Enable gzip compression |
| `withoutCompression()` | Disable gzip compression |
| `configureCompression($threshold, $level)` | Configure compression settings |

### Request (Additional Methods)

| Method | Description |
|--------|-------------|
| `getContentStream()` | Get stream handle for body |
| `readContentChunked($callback, $size)` | Read body in chunks |
| `setMaxBodySize($bytes)` | Set max request body size |
| `getMaxBodySize()` | Get max request body size |

---

## Troubleshooting

### 404 Errors on All Routes

1. **Check mod_rewrite is enabled:**
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

2. **Check AllowOverride is set:** Ensure your Apache configuration allows `.htaccess` files. See [Apache User Directories](#apache-user-directories-username) above.

3. **Check .htaccess permissions:**
   ```bash
   chmod 644 .htaccess
   ```

### 403 Forbidden / "Unable to read htaccess file"

This is a file permissions issue. Apache cannot read the `.htaccess` file:

```bash
chmod 644 .htaccess
chmod 755 /path/to/your/app
```

### 500 Internal Server Error

1. **Check PHP error logs:** Usually at `/var/log/apache2/error.log`

2. **Enable debug mode:** Set `debug=true` in your `.config` file

3. **Check .htaccess syntax:** Some directives (like `Options`) may not be allowed in your Apache configuration

### Views Not Loading

Ensure view paths in `.config` are relative to your application directory:

```ini
[views]
path=views
cache=cache
```

NanoMVC automatically resolves relative paths against the application's base directory.

### Links Not Working in Subdirectory

When installed in a subdirectory, use `$baseUrl` for all links:

```php
<?php $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<a href="<?= $baseUrl ?>/users">Users</a>
```

---

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Submit a pull request

---

## License

NanoMVC is released under the [GPL-3.0-or-later](LICENSE) license.

Copyright 2024-present Paige Julianne Sullivan
