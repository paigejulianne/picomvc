# Session Management

NanoMVC provides secure session management with flash messages, CSRF protection, and custom storage drivers.

## Table of Contents

- [Getting Started](#getting-started)
- [Basic Operations](#basic-operations)
- [Flash Messages](#flash-messages)
- [CSRF Protection](#csrf-protection)
- [Session Configuration](#session-configuration)
- [Custom Storage Drivers](#custom-storage-drivers)
- [Security Best Practices](#security-best-practices)

---

## Getting Started

Sessions are automatically available through the static `Session` class. The session starts automatically when you first access session data.

```php
use PaigeJulianne\NanoMVC\Session;

// Sessions start automatically on first use
Session::set('user_id', 123);
$userId = Session::get('user_id');
```

---

## Basic Operations

### Setting Values

```php
use PaigeJulianne\NanoMVC\Session;

// Set a single value
Session::set('user_id', 123);
Session::set('username', 'alice');

// Set complex data
Session::set('user', [
    'id' => 123,
    'name' => 'Alice',
    'email' => 'alice@example.com'
]);

// Set with nested keys
Session::set('cart', []);
$cart = Session::get('cart');
$cart[] = ['product_id' => 1, 'quantity' => 2];
Session::set('cart', $cart);
```

### Getting Values

```php
// Get a value
$userId = Session::get('user_id');

// Get with default value
$theme = Session::get('theme', 'light');
$locale = Session::get('locale', 'en');

// Get complex data
$user = Session::get('user');
echo $user['name'];  // 'Alice'

// Get all session data
$allData = Session::all();
```

### Checking and Removing

```php
// Check if key exists
if (Session::has('user_id')) {
    // User is logged in
}

// Remove a single value
Session::forget('temp_data');

// Clear all session data (keep session alive)
Session::flush();

// Destroy session completely (logout)
Session::destroy();
```

### Session Lifecycle

```php
// Manually start session (usually automatic)
Session::start();

// Check if session is started
if (Session::isStarted()) {
    // Session is active
}

// Get session ID
$sessionId = Session::getId();

// Set session ID (must be before start)
Session::setId($customId);

// Regenerate session ID (security best practice after login)
Session::regenerate();
```

---

## Flash Messages

Flash messages persist for only the next request, perfect for form feedback.

### Setting Flash Messages

```php
// In your controller
class UsersController extends Controller
{
    public function store(Request $request): Response
    {
        // ... create user ...

        // Flash success message
        Session::flash('success', 'User created successfully!');

        return $this->redirect('/users');
    }

    public function update(Request $request): Response
    {
        try {
            // ... update logic ...
            Session::flash('success', 'Profile updated!');
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to update profile.');
        }

        return $this->redirect('/profile');
    }

    public function destroy(Request $request): Response
    {
        // ... delete logic ...

        Session::flash('warning', 'User has been deleted.');

        return $this->redirect('/users');
    }
}
```

### Displaying Flash Messages

```php
<!-- In your view/layout -->
<?php if ($success = Session::getFlash('success')): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<?php if ($error = Session::getFlash('error')): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if ($warning = Session::getFlash('warning')): ?>
    <div class="alert alert-warning">
        <?= htmlspecialchars($warning) ?>
    </div>
<?php endif; ?>
```

### Flash with Multiple Values

```php
// Flash validation errors
Session::flash('errors', [
    'email' => 'Invalid email format',
    'password' => 'Password too short'
]);

// Flash old input
Session::flash('old_input', $request->all());

// In view
$errors = Session::getFlash('errors', []);
$oldInput = Session::getFlash('old_input', []);
?>

<input type="email"
       name="email"
       value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>"
       class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>">

<?php if (isset($errors['email'])): ?>
    <span class="error"><?= htmlspecialchars($errors['email']) ?></span>
<?php endif; ?>
```

---

## CSRF Protection

NanoMVC includes built-in CSRF (Cross-Site Request Forgery) protection.

### Generating Tokens

```php
// Get CSRF token (creates one if needed)
$token = Session::csrfToken();
```

### In Forms

```php
<form method="POST" action="/users">
    <!-- Include CSRF token -->
    <input type="hidden" name="_token" value="<?= Session::csrfToken() ?>">

    <input type="text" name="name" required>
    <input type="email" name="email" required>

    <button type="submit">Create User</button>
</form>
```

### With JavaScript/AJAX

```php
<!-- Add token to page for JavaScript access -->
<meta name="csrf-token" content="<?= Session::csrfToken() ?>">

<script>
// Get token from meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Include in fetch requests
fetch('/api/users', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({ name: 'Alice' })
});

// Or with axios
axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
</script>
```

### Verifying Tokens

```php
// Manual verification (usually use CsrfMiddleware instead)
$token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');

if (!Session::verifyCsrfToken($token)) {
    return Response::json(['error' => 'Invalid CSRF token'], 419);
}
```

### Using CSRF Middleware

```php
use PaigeJulianne\NanoMVC\CsrfMiddleware;

// Apply to all POST/PUT/DELETE routes
Router::group(['middleware' => [new CsrfMiddleware()]], function() {
    Router::post('/users', [UsersController::class, 'store']);
    Router::put('/users/{id}', [UsersController::class, 'update']);
    Router::delete('/users/{id}', [UsersController::class, 'destroy']);
});

// Exclude specific paths (webhooks, APIs)
$csrf = new CsrfMiddleware([
    '/api/webhooks/*',
    '/stripe/*'
]);

Router::group(['middleware' => [$csrf]], function() {
    // Protected routes
});
```

---

## Session Configuration

### Basic Configuration

```php
use PaigeJulianne\NanoMVC\Session;

// Configure before first use
Session::configure([
    'name' => 'my_app_session',   // Cookie name
    'lifetime' => 7200,            // 2 hours (seconds)
    'path' => '/',                 // Cookie path
    'domain' => '',                // Cookie domain
    'secure' => true,              // HTTPS only
    'httponly' => true,            // No JavaScript access
    'samesite' => 'Lax'            // CSRF protection
]);
```

### Configuration Options

| Option | Default | Description |
|--------|---------|-------------|
| `name` | `nanomvc_session` | Session cookie name |
| `lifetime` | `7200` | Session lifetime in seconds (2 hours) |
| `path` | `/` | Cookie path |
| `domain` | `''` | Cookie domain (empty = current domain) |
| `secure` | `false` | Only send over HTTPS |
| `httponly` | `true` | Prevent JavaScript access |
| `samesite` | `Lax` | SameSite attribute (Lax, Strict, None) |

### Production Configuration

```php
// config/session.php
Session::configure([
    'name' => 'myapp_session',
    'lifetime' => 86400,        // 24 hours
    'path' => '/',
    'domain' => '.example.com', // Include subdomains
    'secure' => true,           // HTTPS required
    'httponly' => true,         // Always true
    'samesite' => 'Lax'         // Or 'Strict' for more security
]);
```

### Environment-Based Configuration

```php
$isProduction = getenv('APP_ENV') === 'production';

Session::configure([
    'name' => 'app_session',
    'lifetime' => $isProduction ? 86400 : 7200,
    'secure' => $isProduction,
    'samesite' => $isProduction ? 'Strict' : 'Lax'
]);
```

---

## Custom Storage Drivers

### File-Based Driver

```php
use PaigeJulianne\NanoMVC\Session;
use PaigeJulianne\NanoMVC\FileSessionDriver;

// Use custom file storage location
$driver = new FileSessionDriver(
    savePath: '/var/sessions/myapp',
    lifetime: 7200
);

Session::setDriver($driver);
```

### Creating a Custom Driver

```php
use PaigeJulianne\NanoMVC\SessionDriver;

class RedisSessionDriver implements SessionDriver
{
    private \Redis $redis;
    private int $lifetime;
    private string $prefix = 'session:';

    public function __construct(\Redis $redis, int $lifetime = 7200)
    {
        $this->redis = $redis;
        $this->lifetime = $lifetime;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $data = $this->redis->get($this->prefix . $id);
        return $data !== false ? $data : '';
    }

    public function write(string $id, string $data): bool
    {
        return $this->redis->setex(
            $this->prefix . $id,
            $this->lifetime,
            $data
        );
    }

    public function destroy(string $id): bool
    {
        return $this->redis->del($this->prefix . $id) > 0;
    }

    public function gc(int $max_lifetime): int|false
    {
        // Redis handles expiration automatically
        return 0;
    }
}

// Usage
$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);

Session::setDriver(new RedisSessionDriver($redis, 7200));
```

### Database Driver Example

```php
class DatabaseSessionDriver implements SessionDriver
{
    private \PDO $pdo;
    private int $lifetime;

    public function __construct(\PDO $pdo, int $lifetime = 7200)
    {
        $this->pdo = $pdo;
        $this->lifetime = $lifetime;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT data FROM sessions WHERE id = ? AND expires_at > NOW()'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $row['data'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $expires = date('Y-m-d H:i:s', time() + $this->lifetime);

        $stmt = $this->pdo->prepare('
            INSERT INTO sessions (id, data, expires_at)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE data = ?, expires_at = ?
        ');

        return $stmt->execute([$id, $data, $expires, $data, $expires]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE expires_at < NOW()');
        $stmt->execute();
        return $stmt->rowCount();
    }
}

// Database schema
/*
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    data TEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX idx_expires (expires_at)
);
*/
```

---

## Security Best Practices

### 1. Regenerate Session ID After Login

```php
class AuthController extends Controller
{
    public function login(Request $request): Response
    {
        $credentials = $this->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::findByEmail($credentials['email']);

        if ($user && password_verify($credentials['password'], $user->password)) {
            // Regenerate session ID to prevent session fixation
            Session::regenerate();

            Session::set('user_id', $user->id);
            Session::set('logged_in_at', time());

            return $this->redirect('/dashboard');
        }

        Session::flash('error', 'Invalid credentials');
        return $this->redirect('/login');
    }
}
```

### 2. Destroy Session on Logout

```php
public function logout(Request $request): Response
{
    Session::destroy();
    return $this->redirect('/login');
}
```

### 3. Use Secure Cookie Settings

```php
Session::configure([
    'secure' => true,      // HTTPS only
    'httponly' => true,    // No JavaScript access
    'samesite' => 'Strict' // Strictest CSRF protection
]);
```

### 4. Validate Session Age

```php
class SessionAgeMiddleware
{
    private int $maxAge = 3600; // 1 hour

    public function handle(Request $request): ?Response
    {
        $loggedInAt = Session::get('logged_in_at');

        if ($loggedInAt && (time() - $loggedInAt) > $this->maxAge) {
            Session::destroy();
            return Response::redirect('/login?expired=1');
        }

        return null;
    }
}
```

### 5. Track Session Activity

```php
// Update last activity on each request
class ActivityMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (Session::has('user_id')) {
            Session::set('last_activity', time());
        }
        return null;
    }
}

// Check for inactivity
class InactivityMiddleware
{
    private int $timeout = 1800; // 30 minutes

    public function handle(Request $request): ?Response
    {
        $lastActivity = Session::get('last_activity', 0);

        if ($lastActivity && (time() - $lastActivity) > $this->timeout) {
            Session::flush();
            Session::flash('message', 'Session expired due to inactivity');
            return Response::redirect('/login');
        }

        return null;
    }
}
```

---

## Complete Example

```php
// AuthController.php
class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        if (Session::has('user_id')) {
            return $this->redirect('/dashboard');
        }

        return $this->view('auth.login', [
            'error' => Session::getFlash('error')
        ]);
    }

    public function login(Request $request): Response
    {
        try {
            $credentials = $this->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $user = User::findByEmail($credentials['email']);

            if (!$user || !password_verify($credentials['password'], $user->password)) {
                Session::flash('error', 'Invalid email or password');
                return $this->redirect('/login');
            }

            // Regenerate session ID
            Session::regenerate();

            // Store user data
            Session::set('user_id', $user->id);
            Session::set('user_name', $user->name);
            Session::set('logged_in_at', time());

            Session::flash('success', 'Welcome back, ' . $user->name . '!');

            return $this->redirect('/dashboard');

        } catch (ValidationException $e) {
            Session::flash('errors', $e->getErrors());
            Session::flash('old', $request->except(['password']));
            return $this->redirect('/login');
        }
    }

    public function logout(Request $request): Response
    {
        $name = Session::get('user_name', 'User');
        Session::destroy();
        Session::flash('success', "Goodbye, $name!");
        return $this->redirect('/login');
    }
}
```

---

## Next Steps

- [Middleware](middleware.md) - Authentication middleware
- [Security](security.md) - Security best practices
- [Rate Limiting](rate-limiting.md) - Throttle requests
