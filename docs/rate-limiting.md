# Rate Limiting

NanoMVC provides built-in rate limiting to protect your application from abuse.

## Table of Contents

- [Quick Start](#quick-start)
- [ThrottleMiddleware](#throttlemiddleware)
- [RateLimiter Class](#ratelimiter-class)
- [Storage Backends](#storage-backends)
- [Advanced Patterns](#advanced-patterns)

---

## Quick Start

The fastest way to add rate limiting is with ThrottleMiddleware:

```php
use PaigeJulianne\NanoMVC\{Router, ThrottleMiddleware};

// 60 requests per minute
Router::get('/api/search', [SearchController::class, 'index'], [
    new ThrottleMiddleware(60, 1)
]);
```

---

## ThrottleMiddleware

### Constructor

```php
new ThrottleMiddleware(int $maxAttempts, int $decayMinutes)
```

| Parameter | Description |
|-----------|-------------|
| `$maxAttempts` | Maximum requests allowed |
| `$decayMinutes` | Time window in minutes |

### Examples

```php
// Login: 5 attempts per minute (brute force protection)
Router::post('/login', [AuthController::class, 'login'], [
    new ThrottleMiddleware(5, 1)
]);

// API: 100 requests per minute
Router::group(['prefix' => 'api', 'middleware' => [new ThrottleMiddleware(100, 1)]], function() {
    Router::get('/users', [ApiController::class, 'users']);
    Router::get('/posts', [ApiController::class, 'posts']);
});

// Heavy operation: 10 per hour
Router::post('/export', [ExportController::class, 'generate'], [
    new ThrottleMiddleware(10, 60)
]);

// Very restrictive: 3 per day
Router::post('/password-reset', [AuthController::class, 'resetPassword'], [
    new ThrottleMiddleware(3, 1440)
]);
```

### Response Headers

When rate limited, the middleware adds these headers:

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 0
Retry-After: 45
```

### Rate Limited Response

```json
{
    "error": "Too many requests",
    "retry_after": 45
}
```

HTTP Status: 429 Too Many Requests

---

## RateLimiter Class

For more control, use the RateLimiter class directly:

```php
use PaigeJulianne\NanoMVC\RateLimiter;
use PaigeJulianne\NanoMVC\FileRateLimitStore;

$store = new FileRateLimitStore('/tmp/rate-limits');
$limiter = new RateLimiter($store);
```

### Methods

| Method | Description |
|--------|-------------|
| `attempt($key, $max, $decay): bool` | Check and record attempt |
| `tooManyAttempts($key, $max): bool` | Check if limit exceeded |
| `remaining($key, $max): int` | Get remaining attempts |
| `availableIn($key): int` | Seconds until available |
| `clear($key)` | Clear attempts for key |

### Example Usage

```php
class LoginController extends Controller
{
    private RateLimiter $limiter;

    public function __construct()
    {
        $store = new FileRateLimitStore('/tmp/rate-limits');
        $this->limiter = new RateLimiter($store);
    }

    public function login(Request $request): Response
    {
        $email = $request->input('email');
        $key = 'login:' . $email;

        // Check rate limit
        if (!$this->limiter->attempt($key, maxAttempts: 5, decayMinutes: 1)) {
            $retryAfter = $this->limiter->availableIn($key);

            return $this->json([
                'error' => 'Too many login attempts',
                'retry_after' => $retryAfter
            ], 429)->header('Retry-After', $retryAfter);
        }

        // Attempt login
        if ($this->authenticate($email, $request->input('password'))) {
            // Clear rate limit on success
            $this->limiter->clear($key);

            Session::regenerate();
            Session::set('user_id', $user->id);

            return $this->redirect('/dashboard');
        }

        return $this->json(['error' => 'Invalid credentials'], 401);
    }
}
```

---

## Storage Backends

### FileRateLimitStore

Default file-based storage. Good for single-server deployments.

```php
use PaigeJulianne\NanoMVC\FileRateLimitStore;

$store = new FileRateLimitStore('/tmp/rate-limits');
```

### Custom Store

Implement `RateLimitStore` for Redis, database, or other backends:

```php
use PaigeJulianne\NanoMVC\RateLimitStore;

class RedisRateLimitStore implements RateLimitStore
{
    private \Redis $redis;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    public function get(string $key): array
    {
        $data = $this->redis->get('ratelimit:' . $key);
        return $data ? json_decode($data, true) : ['attempts' => 0, 'reset_at' => 0];
    }

    public function set(string $key, array $data, int $ttl): void
    {
        $this->redis->setex(
            'ratelimit:' . $key,
            $ttl,
            json_encode($data)
        );
    }

    public function delete(string $key): void
    {
        $this->redis->del('ratelimit:' . $key);
    }
}

// Usage
$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);

$store = new RedisRateLimitStore($redis);
$limiter = new RateLimiter($store);
```

### Database Store

```php
class DatabaseRateLimitStore implements RateLimitStore
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function get(string $key): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT attempts, reset_at FROM rate_limits WHERE `key` = ?'
        );
        $stmt->execute([$key]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row || $row['reset_at'] < time()) {
            return ['attempts' => 0, 'reset_at' => 0];
        }

        return [
            'attempts' => (int) $row['attempts'],
            'reset_at' => (int) $row['reset_at']
        ];
    }

    public function set(string $key, array $data, int $ttl): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO rate_limits (`key`, attempts, reset_at)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE attempts = ?, reset_at = ?
        ');
        $stmt->execute([
            $key,
            $data['attempts'],
            $data['reset_at'],
            $data['attempts'],
            $data['reset_at']
        ]);
    }

    public function delete(string $key): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM rate_limits WHERE `key` = ?');
        $stmt->execute([$key]);
    }
}

// Database schema
/*
CREATE TABLE rate_limits (
    `key` VARCHAR(255) PRIMARY KEY,
    attempts INT NOT NULL DEFAULT 0,
    reset_at INT NOT NULL,
    INDEX idx_reset (reset_at)
);
*/
```

---

## Advanced Patterns

### Different Limits per User Type

```php
class AdaptiveThrottleMiddleware
{
    public function handle(Request $request): ?Response
    {
        $user = Session::get('user');

        // Different limits based on user type
        if ($user && $user['is_premium']) {
            $maxAttempts = 1000;  // Premium users
        } elseif ($user) {
            $maxAttempts = 100;   // Regular users
        } else {
            $maxAttempts = 20;    // Anonymous
        }

        $key = $this->resolveKey($request);
        $limiter = new RateLimiter(new FileRateLimitStore('/tmp/rate-limits'));

        if (!$limiter->attempt($key, $maxAttempts, 1)) {
            return Response::json([
                'error' => 'Rate limit exceeded',
                'retry_after' => $limiter->availableIn($key)
            ], 429);
        }

        return null;
    }

    private function resolveKey(Request $request): string
    {
        if ($userId = Session::get('user_id')) {
            return 'user:' . $userId;
        }
        return 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
}
```

### Endpoint-Specific Limits

```php
class ApiRateLimiter
{
    private array $limits = [
        'search' => [30, 1],      // 30 per minute
        'create' => [10, 1],      // 10 per minute
        'export' => [5, 60],      // 5 per hour
        'default' => [100, 1]     // 100 per minute
    ];

    public function limitFor(string $action, Request $request): ?Response
    {
        [$max, $decay] = $this->limits[$action] ?? $this->limits['default'];

        $key = $action . ':' . $this->getUserKey($request);
        $limiter = new RateLimiter(new FileRateLimitStore('/tmp/rate-limits'));

        if (!$limiter->attempt($key, $max, $decay)) {
            return Response::json([
                'error' => 'Rate limit exceeded for ' . $action,
                'limit' => $max,
                'retry_after' => $limiter->availableIn($key)
            ], 429);
        }

        return null;
    }

    private function getUserKey(Request $request): string
    {
        return Session::get('user_id') ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

// Usage in controller
class ApiController extends Controller
{
    private ApiRateLimiter $rateLimiter;

    public function __construct()
    {
        $this->rateLimiter = new ApiRateLimiter();
    }

    public function search(Request $request): Response
    {
        if ($response = $this->rateLimiter->limitFor('search', $request)) {
            return $response;
        }

        // Perform search...
        return $this->json($results);
    }

    public function export(Request $request): Response
    {
        if ($response = $this->rateLimiter->limitFor('export', $request)) {
            return $response;
        }

        // Generate export...
        return $this->json(['file' => $path]);
    }
}
```

### Sliding Window

The default implementation uses a fixed window. For sliding window:

```php
class SlidingWindowLimiter
{
    private \Redis $redis;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    public function attempt(string $key, int $max, int $windowSeconds): bool
    {
        $now = microtime(true);
        $windowStart = $now - $windowSeconds;

        // Remove old entries
        $this->redis->zRemRangeByScore($key, 0, $windowStart);

        // Count current entries
        $count = $this->redis->zCard($key);

        if ($count >= $max) {
            return false;
        }

        // Add current request
        $this->redis->zAdd($key, $now, $now . ':' . uniqid());
        $this->redis->expire($key, $windowSeconds);

        return true;
    }

    public function remaining(string $key, int $max): int
    {
        $count = $this->redis->zCard($key);
        return max(0, $max - $count);
    }
}
```

---

## Next Steps

- [Middleware](middleware.md) - Custom middleware
- [Security](security.md) - Security best practices
- [Performance](performance.md) - Optimization techniques
