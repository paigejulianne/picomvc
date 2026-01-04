# Security

NanoMVC provides built-in security features to protect your application from common vulnerabilities.

## Table of Contents

- [CSRF Protection](#csrf-protection)
- [XSS Prevention](#xss-prevention)
- [SQL Injection Prevention](#sql-injection-prevention)
- [Session Security](#session-security)
- [Rate Limiting](#rate-limiting)
- [Input Validation](#input-validation)
- [Security Headers](#security-headers)
- [Production Checklist](#production-checklist)

---

## CSRF Protection

Cross-Site Request Forgery (CSRF) attacks trick users into performing unintended actions.

### Using CSRF Tokens

```php
use PaigeJulianne\NanoMVC\Session;

// Generate/get token
$token = Session::csrfToken();
```

### In HTML Forms

```html
<form method="POST" action="/users">
    <input type="hidden" name="_token" value="<?= Session::csrfToken() ?>">

    <input type="text" name="name" required>
    <input type="email" name="email" required>

    <button type="submit">Create User</button>
</form>
```

### In JavaScript/AJAX

```html
<!-- Add token to page meta -->
<meta name="csrf-token" content="<?= Session::csrfToken() ?>">

<script>
// Read token from meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Include in fetch requests
fetch('/api/users', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify(data)
});

// With axios - set globally
axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
</script>
```

### CSRF Middleware

```php
use PaigeJulianne\NanoMVC\{Router, CsrfMiddleware};

// Protect all state-changing routes
Router::group(['middleware' => [new CsrfMiddleware()]], function() {
    Router::post('/users', [UsersController::class, 'store']);
    Router::put('/users/{id}', [UsersController::class, 'update']);
    Router::delete('/users/{id}', [UsersController::class, 'destroy']);
});

// Exclude specific paths (webhooks, external APIs)
$csrf = new CsrfMiddleware([
    '/webhooks/*',
    '/stripe/*',
    '/api/external/*'
]);
```

### Manual Verification

```php
$token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');

if (!Session::verifyCsrfToken($token)) {
    return Response::json(['error' => 'Invalid CSRF token'], 419);
}
```

---

## XSS Prevention

Cross-Site Scripting (XSS) attacks inject malicious scripts into web pages.

### Always Escape Output

```php
<!-- PHP - ALWAYS escape user data -->
<p><?= htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8') ?></p>

<!-- Create a helper function -->
<?php
function e($string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<p><?= e($userInput) ?></p>
```

### Template Engine Escaping

```blade
{{-- Blade: Auto-escaped --}}
<p>{{ $userInput }}</p>

{{-- Blade: Raw (ONLY for trusted HTML) --}}
<div>{!! $trustedHtml !!}</div>
```

```smarty
{* Smarty: Use escape modifier *}
<p>{$userInput|escape}</p>

{* Or enable auto-escape globally *}
{* $smarty->setEscapeHtml(true); *}
```

### Sanitizing HTML Input

When you need to allow some HTML:

```php
// Using HTMLPurifier (recommended)
$config = HTMLPurifier_Config::createDefault();
$config->set('HTML.Allowed', 'p,b,i,u,a[href],ul,ol,li');
$purifier = new HTMLPurifier($config);

$cleanHtml = $purifier->purify($userHtml);
```

### Content Security Policy

```php
// In middleware or controller
$response->header('Content-Security-Policy',
    "default-src 'self'; " .
    "script-src 'self' 'nonce-" . $nonce . "'; " .
    "style-src 'self' 'unsafe-inline'; " .
    "img-src 'self' data: https:;"
);
```

---

## SQL Injection Prevention

SQL injection attacks manipulate database queries through user input.

### Use Parameterized Queries

```php
// With NanoORM (recommended)
$user = User::find($id);  // Safe - uses prepared statements

$users = User::where('email', '=', $email)->get();  // Safe

// With PDO directly
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);

// NEVER do this
$query = "SELECT * FROM users WHERE email = '$email'";  // VULNERABLE!
```

### NanoORM Best Practices

```php
use PaigeJulianne\NanoORM\Model;

class User extends Model
{
    protected static string $table = 'users';
}

// Safe queries
$user = User::find($id);
$users = User::where('status', '=', 'active')->get();
$users = User::where('age', '>=', 18)->orderBy('name')->limit(10)->get();

// Safe inserts/updates
$user = new User();
$user->email = $userInput;  // Safe - escaped automatically
$user->save();
```

---

## Session Security

### Secure Session Configuration

```php
use PaigeJulianne\NanoMVC\Session;

Session::configure([
    'name' => 'myapp_session',     // Custom session name
    'lifetime' => 7200,             // 2 hours
    'path' => '/',
    'domain' => '',
    'secure' => true,               // HTTPS only
    'httponly' => true,             // No JavaScript access
    'samesite' => 'Lax'             // CSRF protection
]);
```

### Session Fixation Prevention

Regenerate session ID after authentication:

```php
class AuthController extends Controller
{
    public function login(Request $request): Response
    {
        // Validate credentials...

        if ($this->validateCredentials($email, $password)) {
            // Regenerate session ID to prevent fixation
            Session::regenerate();

            Session::set('user_id', $user->id);
            Session::set('logged_in_at', time());

            return $this->redirect('/dashboard');
        }

        return $this->redirect('/login');
    }
}
```

### Session Expiration

```php
class SessionAgeMiddleware
{
    private int $maxAge = 3600;  // 1 hour

    public function handle(Request $request): ?Response
    {
        $loggedInAt = Session::get('logged_in_at');

        if ($loggedInAt && (time() - $loggedInAt) > $this->maxAge) {
            Session::destroy();

            if ($request->expectsJson()) {
                return Response::json(['error' => 'Session expired'], 401);
            }

            return Response::redirect('/login?expired=1');
        }

        return null;
    }
}
```

### Proper Logout

```php
public function logout(Request $request): Response
{
    Session::destroy();  // Completely destroys session
    return $this->redirect('/login');
}
```

---

## Rate Limiting

Prevent brute force and DoS attacks.

### Using ThrottleMiddleware

```php
use PaigeJulianne\NanoMVC\ThrottleMiddleware;

// Login: 5 attempts per minute
Router::post('/login', [AuthController::class, 'login'], [
    new ThrottleMiddleware(5, 1)
]);

// API: 100 requests per minute
Router::group([
    'prefix' => 'api',
    'middleware' => [new ThrottleMiddleware(100, 1)]
], function() {
    // API routes
});

// Heavy operations: 10 per hour
Router::post('/export', [ExportController::class, 'generate'], [
    new ThrottleMiddleware(10, 60)
]);
```

### Rate Limit Headers

The middleware automatically adds these headers:

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
Retry-After: 45 (only when rate limited)
```

### Custom Rate Limiter

```php
use PaigeJulianne\NanoMVC\RateLimiter;
use PaigeJulianne\NanoMVC\FileRateLimitStore;

// Configure storage
$store = new FileRateLimitStore('/tmp/rate-limits');
$limiter = new RateLimiter($store);

// Check rate limit
$key = 'login:' . $request->input('email');

if (!$limiter->attempt($key, maxAttempts: 5, decayMinutes: 1)) {
    return Response::json([
        'error' => 'Too many login attempts',
        'retry_after' => $limiter->availableIn($key)
    ], 429);
}
```

---

## Input Validation

Always validate user input before processing.

### Using Built-in Validation

```php
class UsersController extends Controller
{
    public function store(Request $request): Response
    {
        try {
            $data = $this->validate([
                'name' => 'required|min:2|max:100',
                'email' => 'required|email',
                'password' => 'required|min:8',
                'age' => 'numeric|min:13',
                'website' => 'url'
            ]);

            // $data is validated and safe to use
            $user = User::create($data);

        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }
    }
}
```

### Validation Rules

| Rule | Description |
|------|-------------|
| `required` | Field must be present and not empty |
| `email` | Must be valid email format |
| `url` | Must be valid URL |
| `numeric` | Must be numeric |
| `integer` | Must be integer |
| `min:n` | Minimum string length |
| `max:n` | Maximum string length |
| `in:a,b,c` | Must be one of listed values |
| `alpha` | Letters only |
| `alphanumeric` | Letters and numbers only |

### Additional Validation

```php
// Type checking
$id = filter_var($request->input('id'), FILTER_VALIDATE_INT);
if ($id === false) {
    return $this->json(['error' => 'Invalid ID'], 400);
}

// Whitelist values
$status = $request->input('status');
if (!in_array($status, ['active', 'pending', 'inactive'])) {
    return $this->json(['error' => 'Invalid status'], 400);
}

// File validation
$file = $request->file('upload');
$allowed = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed)) {
    return $this->json(['error' => 'Invalid file type'], 400);
}
```

---

## Security Headers

Add security headers to protect against common attacks.

### Security Headers Middleware

```php
class SecurityHeadersMiddleware
{
    public function handle(Request $request): ?Response
    {
        // Headers will be added after response is generated
        // Store middleware marker for later
        $GLOBALS['add_security_headers'] = true;

        return null;
    }
}

// In your bootstrap or app configuration
register_shutdown_function(function() {
    if (!empty($GLOBALS['add_security_headers']) && !headers_sent()) {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');

        // Prevent MIME sniffing
        header('X-Content-Type-Options: nosniff');

        // XSS protection (legacy browsers)
        header('X-XSS-Protection: 1; mode=block');

        // HTTPS enforcement
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'");
    }
});
```

### Adding Headers to Responses

```php
public function handle(Request $request): Response
{
    return $this->view('home')
        ->header('X-Frame-Options', 'DENY')
        ->header('X-Content-Type-Options', 'nosniff')
        ->header('X-XSS-Protection', '1; mode=block');
}
```

---

## Production Checklist

### Before Deployment

1. **Disable Debug Mode**
   ```ini
   [app]
   debug=false
   ```

2. **Use HTTPS**
   ```php
   Session::configure([
       'secure' => true,
       'samesite' => 'Lax'
   ]);
   ```

3. **Configure Error Handling**
   ```php
   Router::setErrorHandler(function(\Throwable $e, Request $request) {
       error_log($e->getMessage());  // Log error

       // Don't expose details in production
       return Response::json(['error' => 'Internal server error'], 500);
   });
   ```

4. **Set Proper File Permissions**
   ```bash
   chmod 644 *.php
   chmod 755 cache/
   chmod 700 .config
   ```

5. **Remove Development Files**
   ```bash
   rm -rf tests/
   rm phpunit.xml
   ```

### Ongoing Security

- Keep PHP and dependencies updated
- Monitor error logs
- Use rate limiting on sensitive endpoints
- Regularly rotate session secrets
- Back up database and files
- Use environment variables for secrets

### Environment Variables

```php
// Never hardcode secrets
$dbPassword = getenv('DB_PASSWORD');
$apiKey = getenv('API_SECRET_KEY');

// Use .env file (not committed to git)
// DB_PASSWORD=secret123
// API_SECRET_KEY=abc123
```

---

## Next Steps

- [Sessions](sessions.md) - Session configuration
- [Middleware](middleware.md) - Security middleware
- [Validation](validation.md) - Input validation
