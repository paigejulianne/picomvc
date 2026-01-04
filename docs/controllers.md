# Controllers

Controllers handle incoming HTTP requests and return responses. They serve as the central point for your application's business logic.

## Table of Contents

- [Creating Controllers](#creating-controllers)
- [Request Handling](#request-handling)
- [Response Methods](#response-methods)
- [Validation](#validation)
- [Dependency Injection](#dependency-injection)
- [Best Practices](#best-practices)

---

## Creating Controllers

### Basic Controller

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
            'message' => 'Hello, World!'
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

### Registering Controllers

```php
// routes.php
use PaigeJulianne\NanoMVC\Router;

require_once 'controllers/HomeController.php';

Router::get('/', [HomeController::class, 'index']);
Router::get('/about', [HomeController::class, 'about']);
```

### Controller with Constructor

```php
class UsersController extends Controller
{
    private UserRepository $users;
    private MailService $mail;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->mail = new MailService();
    }

    public function index(Request $request): Response
    {
        $users = $this->users->all();
        return $this->view('users.index', ['users' => $users]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email'
        ]);

        $user = $this->users->create($data);
        $this->mail->sendWelcome($user);

        return $this->redirect('/users/' . $user->id);
    }
}
```

---

## Request Handling

### Accessing Request Data

```php
class UsersController extends Controller
{
    public function search(Request $request): Response
    {
        // Query parameters (?q=search&page=2)
        $query = $request->query('q', '');
        $page = $request->query('page', 1);

        // POST data
        $name = $request->input('name');

        // All input (POST + GET merged)
        $all = $request->all();

        // Only specific fields
        $credentials = $request->only(['email', 'password']);

        // All except certain fields
        $data = $request->except(['_token', 'password_confirmation']);

        // Check if field exists
        if ($request->has('email')) {
            // ...
        }

        return $this->json(['results' => $results]);
    }
}
```

### Route Parameters

```php
class ArticlesController extends Controller
{
    // Route: /articles/{category}/{id}
    public function show(Request $request): Response
    {
        // Get single parameter
        $id = $request->param('id');
        $category = $request->param('category');

        // With default value
        $page = $request->param('page', 1);

        // Get all route parameters
        $params = $request->params();
        // ['category' => 'tech', 'id' => '123']

        return $this->view('articles.show', [
            'category' => $category,
            'id' => $id
        ]);
    }
}
```

### Request Metadata

```php
class ApiController extends Controller
{
    public function handle(Request $request): Response
    {
        // HTTP method
        $method = $request->method();  // GET, POST, PUT, etc.

        // Request path
        $path = $request->path();  // /api/users

        // Headers
        $auth = $request->header('Authorization');
        $contentType = $request->header('Content-Type');
        $allHeaders = $request->headers();

        // Cookies
        $session = $request->cookie('session_id');

        // Check request type
        if ($request->isAjax()) {
            // XMLHttpRequest
        }

        if ($request->expectsJson()) {
            // Accept: application/json
        }

        // Raw body
        $rawBody = $request->getContent();

        // JSON body (parsed)
        $jsonData = $request->json();

        return $this->json(['received' => true]);
    }
}
```

### File Uploads

```php
class UploadController extends Controller
{
    public function store(Request $request): Response
    {
        $file = $request->file('avatar');

        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $tmpName = $file['tmp_name'];
            $originalName = $file['name'];
            $size = $file['size'];
            $type = $file['type'];

            // Validate
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($type, $allowedTypes)) {
                return $this->json(['error' => 'Invalid file type'], 400);
            }

            // Move to permanent location
            $destination = 'uploads/' . uniqid() . '_' . $originalName;
            move_uploaded_file($tmpName, $destination);

            return $this->json(['path' => $destination]);
        }

        return $this->json(['error' => 'No file uploaded'], 400);
    }
}
```

---

## Response Methods

### View Response

```php
class PageController extends Controller
{
    public function index(Request $request): Response
    {
        // Basic view
        return $this->view('home');

        // View with data
        return $this->view('home', [
            'title' => 'Welcome',
            'user' => $user
        ]);

        // View with custom status
        return $this->view('errors.not-found', [
            'message' => 'Page not found'
        ], 404);
    }
}
```

### JSON Response

```php
class ApiController extends Controller
{
    public function users(Request $request): Response
    {
        $users = User::all();

        // Basic JSON
        return $this->json($users);

        // With status code
        return $this->json(['error' => 'Not found'], 404);

        // Structured response
        return $this->json([
            'data' => $users,
            'meta' => [
                'total' => count($users),
                'page' => 1
            ]
        ]);
    }
}
```

### Redirect Response

```php
class AuthController extends Controller
{
    public function login(Request $request): Response
    {
        // Basic redirect
        return $this->redirect('/dashboard');

        // With status code (301 permanent)
        return $this->redirect('/new-url', 301);

        // Redirect back (using Referer header)
        $back = $request->header('Referer', '/');
        return $this->redirect($back);
    }
}
```

### Text and HTML Responses

```php
class UtilController extends Controller
{
    // Plain text
    public function robots(Request $request): Response
    {
        $content = "User-agent: *\nDisallow: /admin/";
        return $this->text($content);
    }

    // Raw HTML
    public function widget(Request $request): Response
    {
        return $this->html('<div class="widget">Content</div>');
    }

    // HTML with status
    public function maintenance(Request $request): Response
    {
        return $this->html('<h1>Under Maintenance</h1>', 503);
    }
}
```

### Custom Responses

```php
use PaigeJulianne\NanoMVC\Response;

class DownloadController extends Controller
{
    public function download(Request $request): Response
    {
        $content = file_get_contents('/path/to/file.pdf');

        return (new Response())
            ->setContent($content)
            ->setStatusCode(200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="document.pdf"')
            ->header('Content-Length', strlen($content));
    }

    public function csv(Request $request): Response
    {
        $data = "Name,Email\nAlice,alice@example.com\nBob,bob@example.com";

        return Response::text($data)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="export.csv"');
    }
}
```

---

## Validation

### Basic Validation

```php
class UsersController extends Controller
{
    public function store(Request $request): Response
    {
        try {
            $data = $this->validate([
                'name' => 'required|min:2|max:100',
                'email' => 'required|email',
                'age' => 'numeric|min:18',
                'role' => 'in:user,admin,moderator'
            ]);

            // Validation passed - $data contains validated fields
            $user = User::create($data);

            return $this->redirect('/users/' . $user->id);

        } catch (ValidationException $e) {
            // Validation failed
            if ($request->expectsJson()) {
                return $e->toResponse();  // JSON with errors
            }

            return $this->view('users.create', [
                'errors' => $e->getErrors(),
                'old' => $request->all()
            ]);
        }
    }
}
```

### Available Validation Rules

| Rule | Description | Example |
|------|-------------|---------|
| `required` | Field must be present and not empty | `'name' => 'required'` |
| `email` | Must be valid email | `'email' => 'required\|email'` |
| `numeric` | Must be numeric | `'age' => 'numeric'` |
| `integer` | Must be integer | `'count' => 'integer'` |
| `min:n` | Minimum string length | `'password' => 'min:8'` |
| `max:n` | Maximum string length | `'bio' => 'max:500'` |
| `in:a,b,c` | Must be one of values | `'role' => 'in:user,admin'` |
| `url` | Must be valid URL | `'website' => 'url'` |
| `alpha` | Letters only | `'code' => 'alpha'` |
| `alphanumeric` | Letters and numbers only | `'username' => 'alphanumeric'` |

### Combining Rules

```php
$data = $this->validate([
    'username' => 'required|alphanumeric|min:3|max:20',
    'email' => 'required|email',
    'password' => 'required|min:8|max:100',
    'role' => 'required|in:user,admin,moderator',
    'website' => 'url',  // Optional, validated if present
    'age' => 'integer|min:13'
]);
```

### Handling Validation Errors

```php
use PaigeJulianne\NanoMVC\ValidationException;

public function update(Request $request): Response
{
    try {
        $data = $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email'
        ]);

        // Process valid data...
        return $this->redirect('/profile');

    } catch (ValidationException $e) {
        // Get all errors
        $errors = $e->getErrors();
        // ['name' => ['The name field is required.'], 'email' => [...]]

        // API response
        if ($request->expectsJson()) {
            return $e->toResponse();
            // {"errors": {"name": [...], "email": [...]}}
        }

        // Form response - show errors
        return $this->view('profile.edit', [
            'errors' => $errors,
            'old' => $request->all()
        ]);
    }
}
```

---

## Dependency Injection

### Manual Injection

```php
class OrdersController extends Controller
{
    private OrderRepository $orders;
    private PaymentService $payments;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->orders = new OrderRepository();
        $this->payments = new PaymentService();
        $this->notifications = new NotificationService();
    }

    public function store(Request $request): Response
    {
        $data = $this->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1'
        ]);

        $order = $this->orders->create($data);
        $this->payments->charge($order);
        $this->notifications->orderCreated($order);

        return $this->json($order, 201);
    }
}
```

### Service Container Pattern

```php
// Simple service container
class Container
{
    private static array $bindings = [];
    private static array $instances = [];

    public static function bind(string $key, callable $factory): void
    {
        self::$bindings[$key] = $factory;
    }

    public static function singleton(string $key, callable $factory): void
    {
        self::$bindings[$key] = function() use ($key, $factory) {
            if (!isset(self::$instances[$key])) {
                self::$instances[$key] = $factory();
            }
            return self::$instances[$key];
        };
    }

    public static function make(string $key): mixed
    {
        if (isset(self::$bindings[$key])) {
            return (self::$bindings[$key])();
        }
        throw new \Exception("No binding for: $key");
    }
}

// Bootstrap
Container::singleton(UserRepository::class, fn() => new UserRepository());
Container::singleton(MailService::class, fn() => new MailService());

// Controller
class UsersController extends Controller
{
    private UserRepository $users;

    public function __construct()
    {
        $this->users = Container::make(UserRepository::class);
    }
}
```

---

## Best Practices

### Keep Controllers Thin

```php
// Bad: Fat controller with business logic
class UsersController extends Controller
{
    public function store(Request $request): Response
    {
        $data = $this->validate([...]);

        // Too much logic in controller
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $user->save();

        $token = bin2hex(random_bytes(32));
        // ... more email verification logic

        mail($user->email, 'Welcome', $message);

        return $this->redirect('/users');
    }
}

// Good: Thin controller, logic in services
class UsersController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function store(Request $request): Response
    {
        $data = $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email',
            'password' => 'required|min:8'
        ]);

        $user = $this->userService->register($data);

        return $this->redirect('/users/' . $user->id);
    }
}
```

### Single Responsibility

```php
// Each controller handles one resource
class UsersController extends Controller { /* User CRUD */ }
class PostsController extends Controller { /* Post CRUD */ }
class CommentsController extends Controller { /* Comment CRUD */ }

// API controllers separate from web controllers
class Api\UsersController extends Controller { /* JSON responses */ }
class Web\UsersController extends Controller { /* View responses */ }
```

### Consistent Response Format

```php
class ApiController extends Controller
{
    protected function success(mixed $data, int $status = 200): Response
    {
        return $this->json([
            'success' => true,
            'data' => $data
        ], $status);
    }

    protected function error(string $message, int $status = 400, array $errors = []): Response
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return $this->json($response, $status);
    }
}

class UsersApiController extends ApiController
{
    public function index(Request $request): Response
    {
        $users = User::all();
        return $this->success($users);
    }

    public function store(Request $request): Response
    {
        try {
            $data = $this->validate([...]);
            $user = User::create($data);
            return $this->success($user, 201);
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->getErrors());
        }
    }
}
```

---

## Next Steps

- [Validation](validation.md) - Detailed validation rules
- [Request & Response](request-response.md) - HTTP handling
- [Views](views.md) - Template rendering
