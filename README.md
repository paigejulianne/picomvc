# NanoMVC

A lightweight MVC framework for PHP 8.0+ with support for Blade and Smarty templates.

**Version 1.0.0** | [Changelog](CHANGELOG.md) | [License: GPL-3.0](LICENSE)

by Paige Julianne Sullivan
[paigejulianne.com](https://paigejulianne.com) | [GitHub](https://github.com/paigejulianne/nanomvc)

---

## Features

- **Minimal footprint**: Single-file framework (~800 lines)
- **Multiple template engines**: PHP, Blade, and Smarty support
- **Simple routing**: Clean URL routing with parameters
- **Zero dependencies**: Only requires PHP 8.0+ (template engines optional)
- **Built-in validation**: Request validation with helpful error messages
- **Integrates with PicoORM**: Seamlessly works with PicoORM for database operations

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

## Integration with PicoORM

### Creating Models

```php
use PaigeJulianne\PicoORM;

class Users extends PicoORM
{
    // Maps to 'users' table automatically
}

class BlogPost extends PicoORM
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
