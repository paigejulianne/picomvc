# Quick Start Guide

Build your first NanoMVC application in 5 minutes.

## Table of Contents

- [Create Project Structure](#create-project-structure)
- [Entry Point](#entry-point)
- [Configuration](#configuration)
- [Routes](#routes)
- [Controllers](#controllers)
- [Views](#views)
- [Running the Application](#running-the-application)

---

## Create Project Structure

```bash
mkdir myapp && cd myapp

# Create directories
mkdir -p controllers views cache

# Install NanoMVC
composer require paigejulianne/nanomvc
```

Your structure:
```
myapp/
├── controllers/
├── views/
├── cache/
├── vendor/
└── composer.json
```

---

## Entry Point

Create `index.php`:

```php
<?php
require_once 'vendor/autoload.php';

use PaigeJulianne\NanoMVC\App;

App::run(__DIR__);
```

---

## Configuration

Create `.config`:

```ini
[app]
name=My First App
debug=true

[views]
engine=php
path=views
cache=cache

[routes]
file=routes.php
```

---

## Routes

Create `routes.php`:

```php
<?php
use PaigeJulianne\NanoMVC\Router;

// Include controllers
require_once 'controllers/HomeController.php';
require_once 'controllers/UsersController.php';

// Home routes
Router::get('/', [HomeController::class, 'index']);
Router::get('/about', [HomeController::class, 'about']);

// User routes
Router::get('/users', [UsersController::class, 'index']);
Router::get('/users/{id}', [UsersController::class, 'show']);
Router::post('/users', [UsersController::class, 'store']);

// API routes
Router::group(['prefix' => 'api'], function() {
    Router::get('/users', [UsersController::class, 'apiList']);
    Router::get('/users/{id}', [UsersController::class, 'apiShow']);
});

// Custom 404 handler
Router::setNotFoundHandler(function($request) {
    return '<h1>404 - Page Not Found</h1>';
});
```

---

## Controllers

### HomeController

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
            'message' => 'Hello from NanoMVC!'
        ]);
    }

    public function about(Request $request): Response
    {
        return $this->view('about', [
            'title' => 'About Us'
        ]);
    }
}
```

### UsersController

Create `controllers/UsersController.php`:

```php
<?php
use PaigeJulianne\NanoMVC\Controller;
use PaigeJulianne\NanoMVC\Request;
use PaigeJulianne\NanoMVC\Response;
use PaigeJulianne\NanoMVC\ValidationException;

class UsersController extends Controller
{
    // Sample data (replace with database in real app)
    private array $users = [
        1 => ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
        2 => ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
        3 => ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com'],
    ];

    public function index(Request $request): Response
    {
        return $this->view('users.index', [
            'title' => 'Users',
            'users' => $this->users
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = $this->users[$id] ?? null;

        if (!$user) {
            return $this->html('<h1>User not found</h1>', 404);
        }

        return $this->view('users.show', [
            'title' => $user['name'],
            'user' => $user
        ]);
    }

    public function store(Request $request): Response
    {
        try {
            $data = $this->validate([
                'name' => 'required|min:2|max:100',
                'email' => 'required|email'
            ]);

            // Save user (database operation in real app)
            // $user = new User();
            // $user->setMulti($data);
            // $user->save();

            return $this->redirect('/users');

        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $e->toResponse();
            }
            return $this->view('users.create', [
                'errors' => $e->getErrors(),
                'old' => $request->all()
            ]);
        }
    }

    // API endpoints
    public function apiList(Request $request): Response
    {
        return $this->json([
            'data' => array_values($this->users),
            'total' => count($this->users)
        ]);
    }

    public function apiShow(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = $this->users[$id] ?? null;

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        return $this->json($user);
    }
}
```

---

## Views

### Layout

Create `views/layout.php`:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'My App') ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        nav { background: #333; padding: 1rem; margin-bottom: 2rem; border-radius: 8px; }
        nav a { color: white; text-decoration: none; margin-right: 1rem; }
        nav a:hover { text-decoration: underline; }
        h1 { color: #2c3e50; margin-bottom: 1rem; }
        .card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #2980b9; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <?php $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
    <nav>
        <a href="<?= $baseUrl ?>/">Home</a>
        <a href="<?= $baseUrl ?>/about">About</a>
        <a href="<?= $baseUrl ?>/users">Users</a>
    </nav>

    <main>
        <?= $content ?? '' ?>
    </main>
</body>
</html>
```

### Home View

Create `views/home.php`:

```php
<?php ob_start(); ?>

<h1><?= htmlspecialchars($title) ?></h1>

<div class="card">
    <h2><?= htmlspecialchars($message) ?></h2>
    <p>This is a simple application built with NanoMVC.</p>

    <h3>Quick Links</h3>
    <ul>
        <li><a href="<?= $baseUrl ?? '' ?>/users">View Users</a></li>
        <li><a href="<?= $baseUrl ?? '' ?>/api/users">API: Get Users (JSON)</a></li>
    </ul>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
```

### About View

Create `views/about.php`:

```php
<?php ob_start(); ?>

<h1><?= htmlspecialchars($title) ?></h1>

<div class="card">
    <p>NanoMVC is a lightweight MVC framework for PHP 8.0+.</p>

    <h3>Features</h3>
    <ul>
        <li>Simple routing with parameters</li>
        <li>Multiple template engines</li>
        <li>Built-in validation</li>
        <li>Session management</li>
        <li>Rate limiting</li>
    </ul>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
```

### Users Index View

Create `views/users/index.php`:

```php
<?php ob_start(); ?>

<h1><?= htmlspecialchars($title) ?></h1>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td>
                    <a href="<?= $baseUrl ?? '' ?>/users/<?= $user['id'] ?>" class="btn">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
```

### User Show View

Create `views/users/show.php`:

```php
<?php ob_start(); ?>

<h1><?= htmlspecialchars($title) ?></h1>

<div class="card">
    <p><strong>ID:</strong> <?= htmlspecialchars($user['id']) ?></p>
    <p><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>

    <p style="margin-top: 1rem;">
        <a href="<?= $baseUrl ?? '' ?>/users" class="btn">Back to Users</a>
    </p>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
```

---

## Running the Application

### 1. Create .htaccess

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule .* index.php [L]
</IfModule>
```

### 2. Set Permissions

```bash
chmod 644 .htaccess
chmod 755 cache
```

### 3. Access Your Application

- Home: `http://localhost/myapp/`
- About: `http://localhost/myapp/about`
- Users: `http://localhost/myapp/users`
- User Detail: `http://localhost/myapp/users/1`
- API: `http://localhost/myapp/api/users`

---

## Next Steps

- [Routing](routing.md) - Advanced routing features
- [Controllers](controllers.md) - Controller patterns
- [Views](views.md) - Template engines
- [Validation](validation.md) - Input validation
- [Sessions](sessions.md) - User sessions
