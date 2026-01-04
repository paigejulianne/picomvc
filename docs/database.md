# Database Integration

NanoMVC integrates seamlessly with [NanoORM](https://github.com/paigejulianne/nanoorm) for database operations.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Defining Models](#defining-models)
- [Basic CRUD Operations](#basic-crud-operations)
- [Querying Data](#querying-data)
- [Relationships](#relationships)
- [Using in Controllers](#using-in-controllers)

---

## Installation

```bash
composer require paigejulianne/nanoorm
```

---

## Configuration

### Via .config File

```ini
[database]
driver=mysql
host=localhost
port=3306
name=myapp
user=root
password=secret
charset=utf8mb4
```

### Programmatic Setup

```php
use PaigeJulianne\NanoORM\Database;

Database::connect([
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'myapp',
    'username' => 'root',
    'password' => 'secret',
    'charset' => 'utf8mb4'
]);
```

### Using Environment Variables

```php
Database::connect([
    'driver' => getenv('DB_DRIVER') ?: 'mysql',
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => (int) (getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_NAME'),
    'username' => getenv('DB_USER'),
    'password' => getenv('DB_PASSWORD'),
    'charset' => 'utf8mb4'
]);
```

---

## Defining Models

### Basic Model

```php
<?php
// models/User.php

use PaigeJulianne\NanoORM\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';
}
```

### Model with Relationships

```php
<?php
// models/Post.php

use PaigeJulianne\NanoORM\Model;

class Post extends Model
{
    protected static string $table = 'posts';

    public function author(): ?User
    {
        return User::find($this->user_id);
    }

    public function comments(): array
    {
        return Comment::where('post_id', '=', $this->id)->get();
    }
}
```

### Model with Custom Methods

```php
<?php
// models/User.php

use PaigeJulianne\NanoORM\Model;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?self
    {
        return self::where('email', '=', $email)->first();
    }

    public static function active(): array
    {
        return self::where('status', '=', 'active')->get();
    }

    public function posts(): array
    {
        return Post::where('user_id', '=', $this->id)->get();
    }

    public function fullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
```

---

## Basic CRUD Operations

### Create

```php
// Method 1: Create and save
$user = new User();
$user->name = 'Alice';
$user->email = 'alice@example.com';
$user->password = password_hash('secret', PASSWORD_DEFAULT);
$user->save();

// Method 2: Set multiple fields
$user = new User();
$user->setMulti([
    'name' => 'Bob',
    'email' => 'bob@example.com',
    'password' => password_hash('secret', PASSWORD_DEFAULT)
]);
$user->save();

// Get the inserted ID
$id = $user->id;
```

### Read

```php
// Find by primary key
$user = User::find(1);

// Find or fail
$user = User::find(1);
if (!$user) {
    throw new NotFoundException('User not found');
}

// Get all
$users = User::all();

// Get first matching
$user = User::where('email', '=', 'alice@example.com')->first();
```

### Update

```php
// Find and update
$user = User::find(1);
$user->name = 'Alice Smith';
$user->save();

// Update multiple fields
$user = User::find(1);
$user->setMulti([
    'name' => 'Alice Smith',
    'email' => 'alice.smith@example.com'
]);
$user->save();
```

### Delete

```php
// Find and delete
$user = User::find(1);
$user->delete();

// Delete by ID
User::destroy(1);

// Delete with condition
User::where('status', '=', 'inactive')->delete();
```

---

## Querying Data

### Where Clauses

```php
// Basic where
$users = User::where('status', '=', 'active')->get();

// Multiple conditions
$users = User::where('status', '=', 'active')
    ->where('role', '=', 'admin')
    ->get();

// Comparison operators
$users = User::where('age', '>=', 18)->get();
$users = User::where('age', '<', 65)->get();
$users = User::where('name', 'LIKE', '%smith%')->get();

// Not equal
$users = User::where('status', '!=', 'banned')->get();
```

### Ordering

```php
// Ascending
$users = User::orderBy('name', 'ASC')->get();

// Descending
$users = User::orderBy('created_at', 'DESC')->get();

// Multiple order columns
$users = User::orderBy('last_name', 'ASC')
    ->orderBy('first_name', 'ASC')
    ->get();
```

### Limiting

```php
// Limit results
$users = User::limit(10)->get();

// Offset for pagination
$users = User::limit(10)->offset(20)->get();

// Simple pagination
$page = 3;
$perPage = 10;
$users = User::limit($perPage)
    ->offset(($page - 1) * $perPage)
    ->get();
```

### Combining Queries

```php
$users = User::where('status', '=', 'active')
    ->where('role', '=', 'user')
    ->orderBy('created_at', 'DESC')
    ->limit(20)
    ->get();
```

### Counting

```php
$count = User::where('status', '=', 'active')->count();
```

---

## Relationships

### One-to-Many

```php
// User has many posts
class User extends Model
{
    protected static string $table = 'users';

    public function posts(): array
    {
        return Post::where('user_id', '=', $this->id)->get();
    }
}

// Post belongs to user
class Post extends Model
{
    protected static string $table = 'posts';

    public function author(): ?User
    {
        return User::find($this->user_id);
    }
}

// Usage
$user = User::find(1);
$posts = $user->posts();

$post = Post::find(1);
$author = $post->author();
```

### Many-to-Many

```php
class User extends Model
{
    protected static string $table = 'users';

    public function roles(): array
    {
        $sql = "SELECT r.* FROM roles r
                JOIN user_roles ur ON r.id = ur.role_id
                WHERE ur.user_id = ?";

        $stmt = Database::query($sql, [$this->id]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Role::class);
    }

    public function attachRole(int $roleId): void
    {
        Database::query(
            "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)",
            [$this->id, $roleId]
        );
    }

    public function detachRole(int $roleId): void
    {
        Database::query(
            "DELETE FROM user_roles WHERE user_id = ? AND role_id = ?",
            [$this->id, $roleId]
        );
    }
}

// Usage
$user = User::find(1);
$roles = $user->roles();
$user->attachRole(2);
$user->detachRole(1);
```

---

## Using in Controllers

### Basic CRUD Controller

```php
<?php
use PaigeJulianne\NanoMVC\{Controller, Request, Response, ValidationException};

class UsersController extends Controller
{
    public function index(Request $request): Response
    {
        $page = (int) $request->query('page', 1);
        $perPage = 20;

        $users = User::orderBy('name')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        $total = User::count();

        return $this->view('users.index', [
            'users' => $users,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = User::find($id);

        if (!$user) {
            return $this->html('<h1>User not found</h1>', 404);
        }

        return $this->view('users.show', [
            'user' => $user,
            'posts' => $user->posts()
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('users.create', [
            'errors' => Session::getFlash('errors', []),
            'old' => Session::getFlash('old', [])
        ]);
    }

    public function store(Request $request): Response
    {
        try {
            $data = $this->validate([
                'name' => 'required|min:2|max:100',
                'email' => 'required|email',
                'password' => 'required|min:8'
            ]);

            // Check for duplicate email
            if (User::findByEmail($data['email'])) {
                throw new ValidationException([
                    'email' => ['This email is already registered.']
                ]);
            }

            $user = new User();
            $user->setMulti([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $user->save();

            Session::flash('success', 'User created successfully!');
            return $this->redirect('/users/' . $user->id);

        } catch (ValidationException $e) {
            Session::flash('errors', $e->getErrors());
            Session::flash('old', $request->except(['password']));
            return $this->redirect('/users/create');
        }
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = User::find($id);

        if (!$user) {
            return $this->html('<h1>User not found</h1>', 404);
        }

        return $this->view('users.edit', [
            'user' => $user,
            'errors' => Session::getFlash('errors', []),
            'old' => Session::getFlash('old', [])
        ]);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = User::find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        try {
            $data = $this->validate([
                'name' => 'required|min:2|max:100',
                'email' => 'required|email'
            ]);

            // Check for duplicate email (excluding current user)
            $existing = User::findByEmail($data['email']);
            if ($existing && $existing->id !== $user->id) {
                throw new ValidationException([
                    'email' => ['This email is already in use.']
                ]);
            }

            $user->setMulti([
                'name' => $data['name'],
                'email' => $data['email'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $user->save();

            Session::flash('success', 'User updated successfully!');
            return $this->redirect('/users/' . $user->id);

        } catch (ValidationException $e) {
            Session::flash('errors', $e->getErrors());
            Session::flash('old', $request->all());
            return $this->redirect('/users/' . $id . '/edit');
        }
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = User::find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        Session::flash('success', 'User deleted successfully!');
        return $this->redirect('/users');
    }
}
```

### API Controller

```php
class ApiUsersController extends Controller
{
    public function index(Request $request): Response
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 20);

        $users = User::orderBy('id')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        return $this->json([
            'data' => array_map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email
            ], $users),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => User::count()
            ]
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = User::find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        return $this->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at
        ]);
    }

    public function store(Request $request): Response
    {
        try {
            $data = $this->validate([
                'name' => 'required|min:2',
                'email' => 'required|email',
                'password' => 'required|min:8'
            ]);

            $user = new User();
            $user->setMulti([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT)
            ]);
            $user->save();

            return $this->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ], 201);

        } catch (ValidationException $e) {
            return $e->toResponse();
        }
    }
}
```

---

## Next Steps

- [Controllers](controllers.md) - Controller patterns
- [Validation](validation.md) - Input validation
- [Quick Start](quick-start.md) - Complete example
