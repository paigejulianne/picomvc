# Request & Response

NanoMVC provides clean abstractions for handling HTTP requests and generating responses.

## Table of Contents

- [Request Object](#request-object)
- [Input Data](#input-data)
- [Route Parameters](#route-parameters)
- [Headers and Cookies](#headers-and-cookies)
- [File Uploads](#file-uploads)
- [Response Object](#response-object)
- [Response Types](#response-types)
- [Response Compression](#response-compression)

---

## Request Object

The `Request` object is automatically injected into your controller methods and route handlers.

### Basic Properties

```php
use PaigeJulianne\NanoMVC\Request;

class MyController extends Controller
{
    public function handle(Request $request): Response
    {
        // HTTP method
        $method = $request->method();  // GET, POST, PUT, PATCH, DELETE

        // Request path
        $path = $request->path();  // /users/123

        // Full URL
        $url = $request->url();  // http://example.com/users/123

        // Check if AJAX
        if ($request->isAjax()) {
            // XMLHttpRequest
        }

        // Check if expects JSON
        if ($request->expectsJson()) {
            // Accept: application/json
        }

        return $this->json(['method' => $method]);
    }
}
```

### Method Override

For HTML forms that only support GET and POST, use method override:

```html
<form method="POST" action="/users/123">
    <input type="hidden" name="_method" value="PUT">
    <!-- form fields -->
</form>
```

Or via header:

```javascript
fetch('/users/123', {
    method: 'POST',
    headers: {
        'X-HTTP-Method-Override': 'DELETE'
    }
});
```

---

## Input Data

### Query Parameters (GET)

```php
// URL: /search?q=hello&page=2&filters[]=a&filters[]=b

$query = $request->query('q');           // 'hello'
$page = $request->query('page', 1);      // 2 (or default 1)
$filters = $request->query('filters');   // ['a', 'b']

// Get all query parameters
$allQuery = $request->queryAll();
// ['q' => 'hello', 'page' => '2', 'filters' => ['a', 'b']]
```

### POST Data

```php
// Get single input
$name = $request->input('name');
$email = $request->input('email', 'default@example.com');

// Get all input
$all = $request->all();

// Get only specific fields
$credentials = $request->only(['email', 'password']);

// Get all except certain fields
$data = $request->except(['_token', 'password_confirmation']);

// Check if field exists
if ($request->has('email')) {
    // Field is present (even if empty)
}

// Check if field exists and not empty
if ($request->filled('email')) {
    // Field exists and has value
}
```

### JSON Request Body

```php
// Automatically parsed for Content-Type: application/json
$data = $request->json();       // Full JSON body as array
$name = $request->json('name'); // Specific key
$nested = $request->json('user.profile.name'); // Dot notation

// Raw body content
$raw = $request->getContent();
```

### Merged Input

The `all()` method merges query params and POST data:

```php
// POST /search?page=1 with body {"q": "hello"}
$all = $request->all();
// ['page' => 1, 'q' => 'hello']

// POST data takes precedence over query params
```

---

## Route Parameters

```php
// Route: GET /users/{id}/posts/{postId}
// Request: GET /users/42/posts/7

class PostsController extends Controller
{
    public function show(Request $request): Response
    {
        // Get individual parameters
        $userId = $request->param('id');       // '42'
        $postId = $request->param('postId');   // '7'

        // With default value
        $page = $request->param('page', 1);    // 1 (not in route)

        // Get all parameters
        $params = $request->params();
        // ['id' => '42', 'postId' => '7']

        return $this->json([
            'userId' => $userId,
            'postId' => $postId
        ]);
    }
}
```

### Parameter Type Casting

```php
// Parameters are always strings - cast as needed
$id = (int) $request->param('id');
$price = (float) $request->param('price');
$active = filter_var($request->param('active'), FILTER_VALIDATE_BOOLEAN);
```

---

## Headers and Cookies

### Request Headers

```php
// Get single header (case-insensitive)
$auth = $request->header('Authorization');
$contentType = $request->header('Content-Type');
$custom = $request->header('X-Custom-Header');

// With default
$accept = $request->header('Accept', 'text/html');

// Get all headers
$headers = $request->headers();
// ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ...', ...]
```

### Cookies

```php
// Get cookie value
$sessionId = $request->cookie('session_id');
$theme = $request->cookie('theme', 'light');  // With default

// Check cookie exists
if ($request->cookie('remember_me')) {
    // Cookie is set
}
```

---

## File Uploads

### Single File

```php
public function upload(Request $request): Response
{
    $file = $request->file('avatar');

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return $this->json(['error' => 'Upload failed'], 400);
    }

    // File info
    $originalName = $file['name'];      // 'photo.jpg'
    $mimeType = $file['type'];          // 'image/jpeg'
    $size = $file['size'];              // 102400 (bytes)
    $tmpPath = $file['tmp_name'];       // '/tmp/php...'

    // Validate file type
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mimeType, $allowed)) {
        return $this->json(['error' => 'Invalid file type'], 400);
    }

    // Validate file size (e.g., max 5MB)
    if ($size > 5 * 1024 * 1024) {
        return $this->json(['error' => 'File too large'], 400);
    }

    // Generate safe filename
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $destination = 'uploads/' . $filename;

    // Move to permanent location
    if (!move_uploaded_file($tmpPath, $destination)) {
        return $this->json(['error' => 'Failed to save file'], 500);
    }

    return $this->json(['path' => $destination]);
}
```

### Multiple Files

```html
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="photos[]" multiple>
</form>
```

```php
public function uploadMultiple(Request $request): Response
{
    $files = $request->file('photos');
    $uploaded = [];

    foreach ($files['name'] as $index => $name) {
        if ($files['error'][$index] !== UPLOAD_ERR_OK) {
            continue;
        }

        $tmpPath = $files['tmp_name'][$index];
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $destination = 'uploads/' . $filename;

        if (move_uploaded_file($tmpPath, $destination)) {
            $uploaded[] = $destination;
        }
    }

    return $this->json(['files' => $uploaded]);
}
```

### Streaming Large Uploads

```php
public function uploadLarge(Request $request): Response
{
    $outputPath = 'uploads/' . uniqid() . '.bin';
    $handle = fopen($outputPath, 'wb');
    $totalBytes = 0;

    // Process in 8KB chunks
    $request->readContentChunked(function(string $chunk) use ($handle, &$totalBytes) {
        fwrite($handle, $chunk);
        $totalBytes += strlen($chunk);
    }, chunkSize: 8192);

    fclose($handle);

    return $this->json([
        'path' => $outputPath,
        'size' => $totalBytes
    ]);
}
```

---

## Response Object

### Creating Responses

```php
use PaigeJulianne\NanoMVC\Response;

// Static factory methods
$response = Response::html('<h1>Hello</h1>');
$response = Response::json(['status' => 'ok']);
$response = Response::text('Plain text');
$response = Response::redirect('/dashboard');

// Manual construction
$response = new Response();
$response->setContent('<h1>Hello</h1>');
$response->setStatusCode(200);
$response->header('Content-Type', 'text/html');
```

### Status Codes

```php
// Common status codes
$response = Response::json($data, 200);      // OK
$response = Response::json($data, 201);      // Created
$response = Response::json(null, 204);       // No Content
$response = Response::redirect('/login', 302); // Found (temporary)
$response = Response::redirect('/new', 301);   // Moved Permanently
$response = Response::json($error, 400);     // Bad Request
$response = Response::json($error, 401);     // Unauthorized
$response = Response::json($error, 403);     // Forbidden
$response = Response::json($error, 404);     // Not Found
$response = Response::json($error, 422);     // Unprocessable Entity
$response = Response::json($error, 500);     // Internal Server Error

// Get/set status
$status = $response->getStatusCode();
$response->setStatusCode(201);
```

### Response Headers

```php
// Set headers
$response->header('Content-Type', 'application/pdf');
$response->header('Content-Disposition', 'attachment; filename="doc.pdf"');
$response->header('Cache-Control', 'no-cache');
$response->header('X-Custom', 'value');

// Chained headers
return Response::json($data)
    ->header('X-Request-Id', $requestId)
    ->header('X-Response-Time', $duration . 'ms');

// Multiple headers at once
$response->headers([
    'X-Powered-By' => 'NanoMVC',
    'X-Version' => '1.0.0'
]);
```

### Response Cookies

```php
// Set cookie
$response->cookie('session_id', $sessionId);

// With options
$response->cookie('remember_me', $token, [
    'expires' => time() + (86400 * 30),  // 30 days
    'path' => '/',
    'domain' => '.example.com',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Delete cookie
$response->cookie('session_id', '', ['expires' => time() - 3600]);
```

---

## Response Types

### JSON Response

```php
class ApiController extends Controller
{
    public function users(Request $request): Response
    {
        $users = User::all();

        // Simple JSON
        return $this->json($users);

        // With status code
        return $this->json($users, 200);

        // Structured response
        return $this->json([
            'data' => $users,
            'meta' => [
                'total' => count($users),
                'page' => 1,
                'per_page' => 20
            ]
        ]);
    }

    public function notFound(Request $request): Response
    {
        return $this->json([
            'error' => 'Resource not found',
            'code' => 'NOT_FOUND'
        ], 404);
    }
}
```

### HTML Response

```php
// Via view (recommended)
return $this->view('users.index', ['users' => $users]);

// Raw HTML
return $this->html('<h1>Hello World</h1>');

// HTML with status
return $this->html('<h1>Not Found</h1>', 404);
```

### Text Response

```php
// Plain text
return $this->text('User-agent: *\nDisallow: /admin/');

// CSV
return Response::text($csvData)
    ->header('Content-Type', 'text/csv')
    ->header('Content-Disposition', 'attachment; filename="export.csv"');
```

### Redirect Response

```php
// Simple redirect
return $this->redirect('/dashboard');

// With status code
return $this->redirect('/login', 302);      // Temporary
return $this->redirect('/new-url', 301);    // Permanent

// Redirect back
$back = $request->header('Referer', '/');
return $this->redirect($back);
```

### File Download

```php
public function download(Request $request): Response
{
    $path = '/path/to/file.pdf';
    $content = file_get_contents($path);

    return (new Response())
        ->setContent($content)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'attachment; filename="document.pdf"')
        ->header('Content-Length', strlen($content));
}

public function inline(Request $request): Response
{
    // Display in browser instead of downloading
    return (new Response())
        ->setContent(file_get_contents('/path/to/file.pdf'))
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="document.pdf"');
}
```

### No Content Response

```php
public function delete(Request $request): Response
{
    $id = $request->param('id');
    User::delete($id);

    // 204 No Content
    return (new Response())->setStatusCode(204);
}
```

---

## Response Compression

NanoMVC automatically compresses responses when beneficial.

### Automatic Compression

Compression is applied when:
1. Client supports gzip (`Accept-Encoding: gzip`)
2. Response exceeds threshold (default 1KB)
3. Compression reduces size

```php
// Automatic compression for large responses
return $this->json($largeDataset);  // Compressed if > 1KB
```

### Configuration

```php
use PaigeJulianne\NanoMVC\Response;

// Configure globally
Response::configureCompression(
    threshold: 1024,  // Minimum bytes to compress
    level: 6          // Compression level (0-9)
);

// Higher compression (smaller output, slower)
Response::configureCompression(threshold: 512, level: 9);

// Faster compression (larger output, faster)
Response::configureCompression(threshold: 2048, level: 4);
```

### Per-Response Control

```php
public function largeData(Request $request): Response
{
    // Default: compression enabled
    return $this->json($largeData);
}

public function binaryData(Request $request): Response
{
    $imageData = file_get_contents('image.png');

    // Disable for already-compressed content
    return (new Response())
        ->setContent($imageData)
        ->header('Content-Type', 'image/png')
        ->withoutCompression();
}

public function forceCompress(Request $request): Response
{
    // Force compression even for small responses
    return Response::text($data)->withCompression();
}
```

---

## Complete Example

```php
class UsersController extends Controller
{
    public function index(Request $request): Response
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 20);
        $search = $request->query('q');

        $users = User::paginate($page, $perPage, $search);

        if ($request->expectsJson()) {
            return $this->json([
                'data' => $users,
                'meta' => ['page' => $page, 'per_page' => $perPage]
            ]);
        }

        return $this->view('users.index', [
            'users' => $users,
            'search' => $search
        ]);
    }

    public function store(Request $request): Response
    {
        try {
            $data = $this->validate([
                'name' => 'required|min:2',
                'email' => 'required|email'
            ]);

            $user = User::create($data);

            if ($request->expectsJson()) {
                return $this->json($user, 201)
                    ->header('Location', '/users/' . $user->id);
            }

            Session::flash('success', 'User created!');
            return $this->redirect('/users/' . $user->id);

        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->json(['errors' => $e->getErrors()], 422);
            }

            Session::flash('errors', $e->getErrors());
            Session::flash('old', $request->except(['password']));
            return $this->redirect('/users/create');
        }
    }
}
```

---

## Next Steps

- [Controllers](controllers.md) - Controller patterns
- [Validation](validation.md) - Input validation
- [Middleware](middleware.md) - Request/response pipeline
