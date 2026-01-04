<?php

namespace PaigeJulianne\NanoMVC;

/**
 * Package NanoMVC
 *
 * A lightweight MVC framework for PHP 8.0+ with support for Blade, Smarty, and Twig templates.
 *
 * @author    Paige Julianne Sullivan <paige@paigejulianne.com> https://paigejulianne.com
 * @copyright 2024-present Paige Julianne Sullivan
 * @license   GPL-3.0-or-later
 * @link      https://github.com/paigejulianne/nanomvc
 * @version   1.0.1
 */

// ============================================================================
// Request Class
// ============================================================================

/**
 * Simple HTTP request wrapper
 */
class Request
{
    private array $query;
    private array $post;
    private array $server;
    private array $cookies;
    private array $files;
    private array $headers;
    private array $routeParams = [];
    private ?string $rawContent = null;
    private bool $contentRead = false;

    /**
     * Maximum request body size (default 10MB)
     */
    private static int $maxBodySize = 10485760;

    public function __construct()
    {
        $this->query = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->cookies = $_COOKIE;
        $this->files = $_FILES;
        $this->headers = $this->parseHeaders();
    }

    /**
     * Set maximum allowed request body size
     */
    public static function setMaxBodySize(int $bytes): void
    {
        self::$maxBodySize = $bytes;
    }

    /**
     * Get maximum allowed request body size
     */
    public static function getMaxBodySize(): int
    {
        return self::$maxBodySize;
    }

    /**
     * Parse request headers from $_SERVER
     * Normalizes header names to uppercase with underscores for consistent lookup
     */
    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                // Store with underscores for consistent lookup
                $name = substr($key, 5);
                $headers[$name] = $value;
            }
        }
        // Also capture Content-Type and Content-Length which don't have HTTP_ prefix
        if (isset($this->server['CONTENT_TYPE'])) {
            $headers['CONTENT_TYPE'] = $this->server['CONTENT_TYPE'];
        }
        if (isset($this->server['CONTENT_LENGTH'])) {
            $headers['CONTENT_LENGTH'] = $this->server['CONTENT_LENGTH'];
        }
        return $headers;
    }

    /**
     * Get the request method (GET, POST, PUT, DELETE, etc.)
     */
    public function method(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';

        // Support method override via _method field or X-HTTP-Method-Override header
        if ($method === 'POST') {
            if (isset($this->post['_method'])) {
                $method = strtoupper($this->post['_method']);
            } elseif (isset($this->headers['X_HTTP_METHOD_OVERRIDE'])) {
                $method = strtoupper($this->headers['X_HTTP_METHOD_OVERRIDE']);
            }
        }

        return $method;
    }

    /**
     * Get the request URI path (relative to script directory)
     */
    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Strip the base path (directory containing index.php)
        $scriptName = $this->server['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);

        if ($basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        return $path;
    }

    /**
     * Get a query parameter
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get all query parameters
     */
    public function allQuery(): array
    {
        return $this->query;
    }

    /**
     * Get a POST parameter
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Get all input (POST + GET)
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    /**
     * Get only specified keys from input
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    /**
     * Get all input except specified keys
     */
    public function except(array $keys): array
    {
        $all = $this->all();
        return array_diff_key($all, array_flip($keys));
    }

    /**
     * Check if input key exists
     */
    public function has(string $key): bool
    {
        return isset($this->post[$key]) || isset($this->query[$key]);
    }

    /**
     * Get a header value
     * Accepts headers in any format: "Content-Type", "content-type", "CONTENT_TYPE"
     */
    public function header(string $name, mixed $default = null): mixed
    {
        // Normalize to uppercase with underscores to match storage format
        $name = strtoupper(str_replace('-', '_', $name));
        return $this->headers[$name] ?? $default;
    }

    /**
     * Get all headers
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get a cookie value
     */
    public function cookie(string $name, mixed $default = null): mixed
    {
        return $this->cookies[$name] ?? $default;
    }

    /**
     * Get an uploaded file
     */
    public function file(string $name): ?array
    {
        return $this->files[$name] ?? null;
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool
    {
        return ($this->header('X-Requested-With') === 'XMLHttpRequest');
    }

    /**
     * Check if request expects JSON response
     */
    public function expectsJson(): bool
    {
        $accept = $this->header('Accept', '');
        return str_contains($accept, 'application/json');
    }

    /**
     * Get raw request body with size limit enforcement
     *
     * @throws \RuntimeException If body exceeds max size
     */
    public function getContent(): string
    {
        // Return cached content if already read
        if ($this->contentRead) {
            return $this->rawContent ?? '';
        }

        $this->contentRead = true;

        // Check Content-Length header first for early rejection
        $contentLength = (int)($this->header('Content-Length', 0));
        if ($contentLength > self::$maxBodySize) {
            throw new \RuntimeException(
                "Request body too large: $contentLength bytes exceeds limit of " . self::$maxBodySize . " bytes"
            );
        }

        // Read with size limit
        $stream = fopen('php://input', 'rb');
        if ($stream === false) {
            $this->rawContent = '';
            return '';
        }

        $content = '';
        $bytesRead = 0;

        while (!feof($stream)) {
            $chunk = fread($stream, 8192);
            if ($chunk === false) {
                break;
            }
            $bytesRead += strlen($chunk);

            if ($bytesRead > self::$maxBodySize) {
                fclose($stream);
                throw new \RuntimeException(
                    "Request body too large: exceeds limit of " . self::$maxBodySize . " bytes"
                );
            }

            $content .= $chunk;
        }

        fclose($stream);
        $this->rawContent = $content;
        return $content;
    }

    /**
     * Get a stream handle for the request body (for large uploads)
     *
     * @return resource|false
     */
    public function getContentStream()
    {
        return fopen('php://input', 'rb');
    }

    /**
     * Read request body in chunks (memory-efficient for large bodies)
     *
     * @param callable $callback Function to call with each chunk: fn(string $chunk): void
     * @param int $chunkSize Size of each chunk in bytes
     * @throws \RuntimeException If body exceeds max size
     */
    public function readContentChunked(callable $callback, int $chunkSize = 8192): void
    {
        $stream = fopen('php://input', 'rb');
        if ($stream === false) {
            return;
        }

        $bytesRead = 0;

        while (!feof($stream)) {
            $chunk = fread($stream, $chunkSize);
            if ($chunk === false) {
                break;
            }
            $bytesRead += strlen($chunk);

            if ($bytesRead > self::$maxBodySize) {
                fclose($stream);
                throw new \RuntimeException(
                    "Request body too large: exceeds limit of " . self::$maxBodySize . " bytes"
                );
            }

            $callback($chunk);
        }

        fclose($stream);
    }

    /**
     * Get JSON decoded body
     *
     * @throws \RuntimeException If body exceeds max size or JSON is invalid
     */
    public function json(): array
    {
        $content = $this->getContent();
        if ($content === '') {
            return [];
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON: ' . json_last_error_msg());
        }

        return $data ?? [];
    }

    /**
     * Set route parameters (called by Router)
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    /**
     * Get a route parameter
     */
    public function param(string $name, mixed $default = null): mixed
    {
        return $this->routeParams[$name] ?? $default;
    }

    /**
     * Get all route parameters
     */
    public function params(): array
    {
        return $this->routeParams;
    }
}

// ============================================================================
// Response Class
// ============================================================================

/**
 * Simple HTTP response wrapper with compression support
 */
class Response
{
    private string $content = '';
    private int $statusCode = 200;
    private array $headers = [];
    private bool $compress = true;

    /**
     * Minimum content length to trigger compression (default 1KB)
     */
    private static int $compressionThreshold = 1024;

    /**
     * Compression level (0-9, default 6)
     */
    private static int $compressionLevel = 6;

    /**
     * Configure compression settings
     */
    public static function configureCompression(int $threshold = 1024, int $level = 6): void
    {
        self::$compressionThreshold = $threshold;
        self::$compressionLevel = max(0, min(9, $level));
    }

    /**
     * Set the response content
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get the response content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the HTTP status code
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set a response header
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set multiple headers
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }
        return $this;
    }

    /**
     * Disable compression for this response
     */
    public function withoutCompression(): self
    {
        $this->compress = false;
        return $this;
    }

    /**
     * Enable compression for this response
     */
    public function withCompression(): self
    {
        $this->compress = true;
        return $this;
    }

    /**
     * Check if client accepts gzip encoding
     */
    private function clientAcceptsGzip(): bool
    {
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        return str_contains($acceptEncoding, 'gzip');
    }

    /**
     * Send the response with optional gzip compression
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        $content = $this->content;
        $shouldCompress = $this->compress
            && $this->clientAcceptsGzip()
            && strlen($content) >= self::$compressionThreshold
            && function_exists('gzencode')
            && !isset($this->headers['Content-Encoding']);

        if ($shouldCompress) {
            $compressed = gzencode($content, self::$compressionLevel);
            if ($compressed !== false && strlen($compressed) < strlen($content)) {
                $content = $compressed;
                $this->headers['Content-Encoding'] = 'gzip';
                $this->headers['Vary'] = 'Accept-Encoding';
            }
        }

        // Set content length
        $this->headers['Content-Length'] = (string)strlen($content);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        echo $content;
    }

    /**
     * Create a JSON response
     * Uses compact JSON in production, pretty-printed in debug mode
     */
    public static function json(mixed $data, int $status = 200): self
    {
        $response = new self();
        $response->setStatusCode($status);
        $response->header('Content-Type', 'application/json');

        // Only use pretty print in debug mode
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if (App::isDebug()) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $response->setContent(json_encode($data, $flags));
        return $response;
    }

    /**
     * Create a redirect response
     */
    public static function redirect(string $url, int $status = 302): self
    {
        $response = new self();
        $response->setStatusCode($status);
        $response->header('Location', $url);
        return $response;
    }

    /**
     * Create a plain text response
     */
    public static function text(string $content, int $status = 200): self
    {
        $response = new self();
        $response->setStatusCode($status);
        $response->header('Content-Type', 'text/plain');
        $response->setContent($content);
        return $response;
    }

    /**
     * Create an HTML response
     */
    public static function html(string $content, int $status = 200): self
    {
        $response = new self();
        $response->setStatusCode($status);
        $response->header('Content-Type', 'text/html; charset=UTF-8');
        $response->setContent($content);
        return $response;
    }
}

// ============================================================================
// Router Class
// ============================================================================

/**
 * High-performance router with parameter support, route indexing, and caching
 */
class Router
{
    /**
     * @var array Registered routes grouped by HTTP method
     */
    private static array $routes = [];

    /**
     * @var array Route index for O(1) lookup by first path segment
     * Structure: $routeIndex[METHOD][firstSegment][] = routeIndex
     */
    private static array $routeIndex = [];

    /**
     * @var array Static routes for exact matching (no parameters)
     * Structure: $staticRoutes[METHOD][path] = route
     */
    private static array $staticRoutes = [];

    /**
     * @var string|null Current route group prefix
     */
    private static ?string $groupPrefix = null;

    /**
     * @var array Current route group middleware
     */
    private static array $groupMiddleware = [];

    /**
     * @var callable|null 404 handler
     */
    private static $notFoundHandler = null;

    /**
     * @var callable|null Error handler
     */
    private static $errorHandler = null;

    /**
     * @var array Cached middleware instances
     */
    private static array $middlewareCache = [];

    /**
     * @var bool Whether routes have been loaded from cache
     */
    private static bool $routesCached = false;

    /**
     * Register a GET route
     */
    public static function get(string $path, callable|array $handler, array $middleware = []): void
    {
        self::addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Register a POST route
     */
    public static function post(string $path, callable|array $handler, array $middleware = []): void
    {
        self::addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Register a PUT route
     */
    public static function put(string $path, callable|array $handler, array $middleware = []): void
    {
        self::addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Register a PATCH route
     */
    public static function patch(string $path, callable|array $handler, array $middleware = []): void
    {
        self::addRoute('PATCH', $path, $handler, $middleware);
    }

    /**
     * Register a DELETE route
     */
    public static function delete(string $path, callable|array $handler, array $middleware = []): void
    {
        self::addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Register a route for any HTTP method
     */
    public static function any(string $path, callable|array $handler, array $middleware = []): void
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            self::addRoute($method, $path, $handler, $middleware);
        }
    }

    /**
     * Register a route for multiple HTTP methods
     */
    public static function match(array $methods, string $path, callable|array $handler, array $middleware = []): void
    {
        foreach ($methods as $method) {
            self::addRoute(strtoupper($method), $path, $handler, $middleware);
        }
    }

    /**
     * Create a route group with shared prefix and/or middleware
     */
    public static function group(array $options, callable $callback): void
    {
        $previousPrefix = self::$groupPrefix;
        $previousMiddleware = self::$groupMiddleware;

        if (isset($options['prefix'])) {
            self::$groupPrefix = ($previousPrefix ?? '') . '/' . trim($options['prefix'], '/');
        }

        if (isset($options['middleware'])) {
            $middleware = is_array($options['middleware']) ? $options['middleware'] : [$options['middleware']];
            self::$groupMiddleware = array_merge($previousMiddleware, $middleware);
        }

        $callback();

        self::$groupPrefix = $previousPrefix;
        self::$groupMiddleware = $previousMiddleware;
    }

    /**
     * Add a route to the routing table with indexing for fast lookup
     */
    private static function addRoute(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        // Apply group prefix
        if (self::$groupPrefix !== null) {
            $path = self::$groupPrefix . '/' . ltrim($path, '/');
        }

        // Normalize path
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        // Merge group middleware with route middleware
        $middleware = array_merge(self::$groupMiddleware, $middleware);

        // Check if this is a static route (no parameters)
        $isStatic = !str_contains($path, '{');

        // Convert path to regex pattern
        $pattern = self::pathToRegex($path);

        $route = [
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
            'isStatic' => $isStatic,
        ];

        // Store in routes array
        $routeIndex = count(self::$routes[$method] ?? []);
        self::$routes[$method][$routeIndex] = $route;

        // Index for fast lookup
        if ($isStatic) {
            // Static routes go in a hash map for O(1) lookup
            self::$staticRoutes[$method][$path] = $route;
        } else {
            // Dynamic routes indexed by first segment
            $firstSegment = self::getFirstSegment($path);
            self::$routeIndex[$method][$firstSegment][] = $routeIndex;
        }
    }

    /**
     * Get the first segment of a path for indexing
     */
    private static function getFirstSegment(string $path): string
    {
        $path = ltrim($path, '/');
        $pos = strpos($path, '/');
        $segment = $pos === false ? $path : substr($path, 0, $pos);

        // If first segment is a parameter, use wildcard key
        if (str_starts_with($segment, '{')) {
            return '*';
        }

        return $segment;
    }

    /**
     * Convert a route path to a regex pattern
     *
     * Supports:
     * - {param} - Required parameter
     * - {param?} - Optional parameter
     * - {param:regex} - Parameter with custom regex
     */
    private static function pathToRegex(string $path): string
    {
        // Escape forward slashes
        $pattern = preg_quote($path, '#');

        // Convert {param} to named capture group
        $pattern = preg_replace(
            '#\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}#',
            '(?P<$1>[^/]+)',
            $pattern
        );

        // Convert {param?} to optional named capture group
        $pattern = preg_replace(
            '#\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\?\\\}#',
            '(?P<$1>[^/]*)?',
            $pattern
        );

        // Convert {param:regex} to named capture group with custom regex
        $pattern = preg_replace_callback(
            '#\\\{([a-zA-Z_][a-zA-Z0-9_]*):([^}]+)\\\}#',
            function ($matches) {
                $name = $matches[1];
                $regex = stripslashes($matches[2]);
                return "(?P<$name>$regex)";
            },
            $pattern
        );

        return '#^' . $pattern . '$#';
    }

    /**
     * Set the 404 not found handler
     */
    public static function setNotFoundHandler(callable $handler): void
    {
        self::$notFoundHandler = $handler;
    }

    /**
     * Set the error handler
     */
    public static function setErrorHandler(callable $handler): void
    {
        self::$errorHandler = $handler;
    }

    /**
     * Dispatch the request to the appropriate handler using optimized lookup
     */
    public static function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        // Normalize path
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        // 1. Try static route lookup first (O(1))
        if (isset(self::$staticRoutes[$method][$path])) {
            $route = self::$staticRoutes[$method][$path];
            $request->setRouteParams([]);
            return self::executeRoute($route, $request);
        }

        // 2. Try indexed dynamic routes
        $firstSegment = self::getFirstSegment($path);
        $candidateIndices = [];

        // Get routes matching the first segment
        if (isset(self::$routeIndex[$method][$firstSegment])) {
            $candidateIndices = self::$routeIndex[$method][$firstSegment];
        }

        // Also check wildcard routes (first segment is a parameter)
        if (isset(self::$routeIndex[$method]['*'])) {
            $candidateIndices = array_merge($candidateIndices, self::$routeIndex[$method]['*']);
        }

        // Check candidate routes
        foreach ($candidateIndices as $routeIdx) {
            $route = self::$routes[$method][$routeIdx];
            if (preg_match($route['pattern'], $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->setRouteParams($params);
                return self::executeRoute($route, $request);
            }
        }

        // 3. Fallback: check all dynamic routes (for edge cases)
        $routes = self::$routes[$method] ?? [];
        foreach ($routes as $route) {
            if ($route['isStatic']) {
                continue; // Already checked
            }
            if (preg_match($route['pattern'], $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->setRouteParams($params);
                return self::executeRoute($route, $request);
            }
        }

        // No route found
        return self::handleNotFound($request);
    }

    /**
     * Execute a matched route with middleware
     */
    private static function executeRoute(array $route, Request $request): Response
    {
        try {
            // Run middleware
            foreach ($route['middleware'] as $middleware) {
                $result = self::runMiddleware($middleware, $request);
                if ($result instanceof Response) {
                    return $result;
                }
            }

            // Call the handler
            return self::callHandler($route['handler'], $request);
        } catch (\Throwable $e) {
            return self::handleError($e, $request);
        }
    }

    /**
     * Run a middleware with instance caching
     */
    private static function runMiddleware(string|callable $middleware, Request $request): ?Response
    {
        if (is_string($middleware) && class_exists($middleware)) {
            // Use cached instance or create new one
            if (!isset(self::$middlewareCache[$middleware])) {
                self::$middlewareCache[$middleware] = new $middleware();
            }
            $instance = self::$middlewareCache[$middleware];

            if (method_exists($instance, 'handle')) {
                return $instance->handle($request);
            }
        } elseif (is_callable($middleware)) {
            return $middleware($request);
        }

        return null;
    }

    /**
     * Call a route handler
     */
    private static function callHandler(callable|array $handler, Request $request): Response
    {
        // Handle [ControllerClass, 'method'] format
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();

            // Inject request into controller if it has the method
            if (method_exists($controller, 'setRequest')) {
                $controller->setRequest($request);
            }

            $result = $controller->$method($request);
        } else {
            $result = $handler($request);
        }

        // Convert result to Response
        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return Response::html($result);
        }

        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }

        return Response::html((string)$result);
    }

    /**
     * Handle 404 not found
     */
    private static function handleNotFound(Request $request): Response
    {
        if (self::$notFoundHandler !== null) {
            $result = (self::$notFoundHandler)($request);
            if ($result instanceof Response) {
                return $result;
            }
            return Response::html((string)$result, 404);
        }

        return Response::html('<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>The requested URL was not found on this server.</p></body></html>', 404);
    }

    /**
     * Handle errors
     */
    private static function handleError(\Throwable $e, Request $request): Response
    {
        if (self::$errorHandler !== null) {
            $result = (self::$errorHandler)($e, $request);
            if ($result instanceof Response) {
                return $result;
            }
            return Response::html((string)$result, 500);
        }

        // Default error response
        $message = App::isDebug()
            ? "<pre>" . htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString()) . "</pre>"
            : 'An error occurred.';

        return Response::html('<!DOCTYPE html><html><head><title>500 Internal Server Error</title></head><body><h1>500 Internal Server Error</h1>' . $message . '</body></html>', 500);
    }

    /**
     * Get all registered routes (for debugging)
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Clear all routes (useful for testing)
     */
    public static function clear(): void
    {
        self::$routes = [];
        self::$routeIndex = [];
        self::$staticRoutes = [];
        self::$groupPrefix = null;
        self::$groupMiddleware = [];
        self::$middlewareCache = [];
    }

    /**
     * Clear middleware cache (useful if middleware state needs resetting)
     */
    public static function clearMiddlewareCache(): void
    {
        self::$middlewareCache = [];
    }

    /**
     * Cache routes to a file for production use
     *
     * @param string $cacheFile Path to cache file
     */
    public static function cacheRoutes(string $cacheFile): void
    {
        $data = [
            'routes' => self::$routes,
            'routeIndex' => self::$routeIndex,
            'staticRoutes' => self::$staticRoutes,
        ];

        // Filter out closures (can't be serialized)
        $data = self::filterSerializableRoutes($data);

        $content = '<?php return ' . var_export($data, true) . ';';
        file_put_contents($cacheFile, $content, LOCK_EX);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($cacheFile, true);
        }
    }

    /**
     * Load routes from cache file
     *
     * @param string $cacheFile Path to cache file
     * @return bool True if cache was loaded successfully
     */
    public static function loadCachedRoutes(string $cacheFile): bool
    {
        if (!file_exists($cacheFile)) {
            return false;
        }

        $data = require $cacheFile;

        if (!is_array($data) || !isset($data['routes'])) {
            return false;
        }

        self::$routes = $data['routes'];
        self::$routeIndex = $data['routeIndex'] ?? [];
        self::$staticRoutes = $data['staticRoutes'] ?? [];
        self::$routesCached = true;

        return true;
    }

    /**
     * Check if routes are loaded from cache
     */
    public static function isRouteCached(): bool
    {
        return self::$routesCached;
    }

    /**
     * Filter routes to only include serializable handlers
     */
    private static function filterSerializableRoutes(array $data): array
    {
        foreach ($data['routes'] as $method => &$routes) {
            foreach ($routes as $idx => &$route) {
                if (isset($route['handler']) && $route['handler'] instanceof \Closure) {
                    // Remove closure routes from cache (they can't be serialized)
                    unset($routes[$idx]);
                    // Also remove from index
                    if (isset($data['staticRoutes'][$method][$route['path']])) {
                        unset($data['staticRoutes'][$method][$route['path']]);
                    }
                }
            }
        }

        // Re-index arrays
        foreach ($data['routes'] as $method => &$routes) {
            $routes = array_values($routes);
        }

        // Rebuild routeIndex based on remaining routes
        $data['routeIndex'] = [];
        foreach ($data['routes'] as $method => $routes) {
            foreach ($routes as $idx => $route) {
                if (!$route['isStatic']) {
                    $firstSegment = self::getFirstSegment($route['path']);
                    $data['routeIndex'][$method][$firstSegment][] = $idx;
                }
            }
        }

        return $data;
    }

    /**
     * Get route statistics for debugging/monitoring
     */
    public static function getStats(): array
    {
        $stats = [
            'total_routes' => 0,
            'static_routes' => 0,
            'dynamic_routes' => 0,
            'by_method' => [],
            'cached_middleware' => count(self::$middlewareCache),
            'routes_cached' => self::$routesCached,
        ];

        foreach (self::$routes as $method => $routes) {
            $count = count($routes);
            $staticCount = count(self::$staticRoutes[$method] ?? []);
            $stats['total_routes'] += $count;
            $stats['static_routes'] += $staticCount;
            $stats['dynamic_routes'] += ($count - $staticCount);
            $stats['by_method'][$method] = $count;
        }

        return $stats;
    }
}

// ============================================================================
// View Class with Template Engine Adapters
// ============================================================================

/**
 * Template engine interface
 */
interface TemplateAdapter
{
    /**
     * Render a template with data
     */
    public function render(string $template, array $data = []): string;

    /**
     * Check if this adapter is available
     */
    public static function isAvailable(): bool;
}

/**
 * Native PHP template adapter
 */
class PhpAdapter implements TemplateAdapter
{
    private string $viewsPath;
    private string $extension;

    public function __construct(string $viewsPath, string $extension = '.php')
    {
        $this->viewsPath = rtrim($viewsPath, '/');
        $this->extension = $extension;
    }

    public function render(string $template, array $data = []): string
    {
        $file = $this->viewsPath . '/' . str_replace('.', '/', $template) . $this->extension;

        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: $template ($file)");
        }

        extract($data);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    public static function isAvailable(): bool
    {
        return true;
    }
}

/**
 * Blade template adapter (requires jenssegers/blade or illuminate/view)
 */
class BladeAdapter implements TemplateAdapter
{
    private string $viewsPath;
    private string $cachePath;
    private ?object $blade = null;

    public function __construct(string $viewsPath, string $cachePath)
    {
        $this->viewsPath = rtrim($viewsPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
        $this->initBlade();
    }

    private function initBlade(): void
    {
        // Try jenssegers/blade first (simpler standalone package)
        if (class_exists('Jenssegers\Blade\Blade')) {
            $this->blade = new \Jenssegers\Blade\Blade($this->viewsPath, $this->cachePath);
            return;
        }

        // Try illuminate/view (Laravel's view component)
        if (class_exists('Illuminate\View\Factory')) {
            $this->blade = $this->createIlluminateBlade();
            return;
        }

        throw new \RuntimeException(
            'Blade templating requires jenssegers/blade or illuminate/view package. ' .
            'Install with: composer require jenssegers/blade'
        );
    }

    private function createIlluminateBlade(): object
    {
        $filesystem = new \Illuminate\Filesystem\Filesystem();
        $eventDispatcher = new \Illuminate\Events\Dispatcher(new \Illuminate\Container\Container());

        $viewResolver = new \Illuminate\View\Engines\EngineResolver();
        $bladeCompiler = new \Illuminate\View\Compilers\BladeCompiler($filesystem, $this->cachePath);

        $viewResolver->register('blade', function () use ($bladeCompiler) {
            return new \Illuminate\View\Engines\CompilerEngine($bladeCompiler);
        });

        $viewFinder = new \Illuminate\View\FileViewFinder($filesystem, [$this->viewsPath]);

        return new \Illuminate\View\Factory($viewResolver, $viewFinder, $eventDispatcher);
    }

    public function render(string $template, array $data = []): string
    {
        if ($this->blade instanceof \Jenssegers\Blade\Blade) {
            return $this->blade->make($template, $data)->render();
        }

        if ($this->blade instanceof \Illuminate\View\Factory) {
            return $this->blade->make($template, $data)->render();
        }

        throw new \RuntimeException('Blade engine not properly initialized');
    }

    public static function isAvailable(): bool
    {
        return class_exists('Jenssegers\Blade\Blade') || class_exists('Illuminate\View\Factory');
    }
}

/**
 * Smarty template adapter
 */
class SmartyAdapter implements TemplateAdapter
{
    private string $viewsPath;
    private string $cachePath;
    private ?\Smarty $smarty = null;

    public function __construct(string $viewsPath, string $cachePath)
    {
        $this->viewsPath = rtrim($viewsPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
        $this->initSmarty();
    }

    private function initSmarty(): void
    {
        if (!class_exists('Smarty')) {
            throw new \RuntimeException(
                'Smarty templating requires smarty/smarty package. ' .
                'Install with: composer require smarty/smarty'
            );
        }

        $this->smarty = new \Smarty();
        $this->smarty->setTemplateDir($this->viewsPath);
        $this->smarty->setCompileDir($this->cachePath . '/compile');
        $this->smarty->setCacheDir($this->cachePath . '/cache');

        // Create directories if they don't exist
        if (!is_dir($this->cachePath . '/compile')) {
            mkdir($this->cachePath . '/compile', 0755, true);
        }
        if (!is_dir($this->cachePath . '/cache')) {
            mkdir($this->cachePath . '/cache', 0755, true);
        }
    }

    public function render(string $template, array $data = []): string
    {
        // Convert dot notation to path
        $templateFile = str_replace('.', '/', $template) . '.tpl';

        foreach ($data as $key => $value) {
            $this->smarty->assign($key, $value);
        }

        return $this->smarty->fetch($templateFile);
    }

    public static function isAvailable(): bool
    {
        return class_exists('Smarty');
    }

    /**
     * Get the underlying Smarty instance for advanced configuration
     */
    public function getSmarty(): \Smarty
    {
        return $this->smarty;
    }
}

/**
 * Twig template adapter
 */
class TwigAdapter implements TemplateAdapter
{
    private string $viewsPath;
    private string $cachePath;
    private ?\Twig\Environment $twig = null;

    public function __construct(string $viewsPath, string $cachePath)
    {
        $this->viewsPath = rtrim($viewsPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
        $this->initTwig();
    }

    private function initTwig(): void
    {
        if (!class_exists('Twig\Environment')) {
            throw new \RuntimeException(
                'Twig templating requires twig/twig package. ' .
                'Install with: composer require twig/twig'
            );
        }

        // Create cache directory if it doesn't exist
        $twigCache = $this->cachePath . '/twig';
        if (!is_dir($twigCache)) {
            mkdir($twigCache, 0755, true);
        }

        $loader = new \Twig\Loader\FilesystemLoader($this->viewsPath);
        $this->twig = new \Twig\Environment($loader, [
            'cache' => $twigCache,
            'auto_reload' => true,
            'strict_variables' => false,
            'autoescape' => 'html',
        ]);
    }

    public function render(string $template, array $data = []): string
    {
        // Convert dot notation to path with .twig extension
        $templateFile = str_replace('.', '/', $template) . '.twig';

        return $this->twig->render($templateFile, $data);
    }

    public static function isAvailable(): bool
    {
        return class_exists('Twig\Environment');
    }

    /**
     * Get the underlying Twig Environment instance for advanced configuration
     */
    public function getTwig(): \Twig\Environment
    {
        return $this->twig;
    }

    /**
     * Add a custom Twig extension
     */
    public function addExtension(\Twig\Extension\ExtensionInterface $extension): void
    {
        $this->twig->addExtension($extension);
    }

    /**
     * Add a custom Twig filter
     */
    public function addFilter(\Twig\TwigFilter $filter): void
    {
        $this->twig->addFilter($filter);
    }

    /**
     * Add a custom Twig function
     */
    public function addFunction(\Twig\TwigFunction $function): void
    {
        $this->twig->addFunction($function);
    }

    /**
     * Add a global variable available in all templates
     */
    public function addGlobal(string $name, mixed $value): void
    {
        $this->twig->addGlobal($name, $value);
    }
}

/**
 * View manager - handles template rendering with multiple engine support
 */
class View
{
    private static ?TemplateAdapter $adapter = null;
    private static string $viewsPath = '';
    private static string $cachePath = '';
    private static string $engine = 'php';
    private static array $sharedData = [];

    /**
     * Configure the view system
     */
    public static function configure(string $viewsPath, string $cachePath = '', string $engine = 'php'): void
    {
        self::$viewsPath = rtrim($viewsPath, '/');
        self::$cachePath = $cachePath ? rtrim($cachePath, '/') : sys_get_temp_dir() . '/nanomvc_cache';
        self::$engine = strtolower($engine);
        self::$adapter = null; // Reset adapter to force re-initialization
    }

    /**
     * Get or create the template adapter
     */
    private static function getAdapter(): TemplateAdapter
    {
        if (self::$adapter !== null) {
            return self::$adapter;
        }

        if (empty(self::$viewsPath)) {
            throw new \RuntimeException(
                'View system not configured. Call View::configure() or App::run() first.'
            );
        }

        // Create cache directory if needed
        if (!is_dir(self::$cachePath)) {
            mkdir(self::$cachePath, 0755, true);
        }

        self::$adapter = match (self::$engine) {
            'blade' => new BladeAdapter(self::$viewsPath, self::$cachePath),
            'smarty' => new SmartyAdapter(self::$viewsPath, self::$cachePath),
            'twig' => new TwigAdapter(self::$viewsPath, self::$cachePath),
            default => new PhpAdapter(self::$viewsPath),
        };

        return self::$adapter;
    }

    /**
     * Render a template
     */
    public static function render(string $template, array $data = []): string
    {
        $data = array_merge(self::$sharedData, $data);
        return self::getAdapter()->render($template, $data);
    }

    /**
     * Share data with all views
     */
    public static function share(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            self::$sharedData = array_merge(self::$sharedData, $key);
        } else {
            self::$sharedData[$key] = $value;
        }
    }

    /**
     * Check if an engine is available
     */
    public static function engineAvailable(string $engine): bool
    {
        return match (strtolower($engine)) {
            'blade' => BladeAdapter::isAvailable(),
            'smarty' => SmartyAdapter::isAvailable(),
            'twig' => TwigAdapter::isAvailable(),
            'php' => true,
            default => false,
        };
    }

    /**
     * Create a response with rendered view
     */
    public static function make(string $template, array $data = [], int $status = 200): Response
    {
        $content = self::render($template, $data);
        return Response::html($content, $status);
    }

    /**
     * Get the current template adapter (for advanced use)
     */
    public static function getTemplateAdapter(): ?TemplateAdapter
    {
        return self::$adapter;
    }
}

// ============================================================================
// Controller Base Class
// ============================================================================

/**
 * Base controller class
 */
abstract class Controller
{
    protected ?Request $request = null;

    /**
     * Set the request instance
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Get the current request
     */
    protected function request(): ?Request
    {
        return $this->request;
    }

    /**
     * Render a view
     */
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return View::make($template, $data, $status);
    }

    /**
     * Return a JSON response
     */
    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /**
     * Return a redirect response
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }

    /**
     * Return a plain text response
     */
    protected function text(string $content, int $status = 200): Response
    {
        return Response::text($content, $status);
    }

    /**
     * Return an HTML response
     */
    protected function html(string $content, int $status = 200): Response
    {
        return Response::html($content, $status);
    }

    /**
     * Validate request input (basic validation)
     *
     * @param array $rules Validation rules ['field' => 'required|email|min:3|max:100']
     * @return array Validated data
     * @throws ValidationException If validation fails
     */
    protected function validate(array $rules): array
    {
        $data = $this->request?->all() ?? [];
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramString] = explode(':', $rule, 2);
                    $params = explode(',', $paramString);
                }

                $error = $this->validateRule($field, $value, $rule, $params);
                if ($error !== null) {
                    $errors[$field][] = $error;
                }
            }

            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $validated;
    }

    /**
     * Validate a single rule
     */
    private function validateRule(string $field, mixed $value, string $rule, array $params): ?string
    {
        return match ($rule) {
            'required' => ($value === null || $value === '') ? "The $field field is required." : null,
            'email' => ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) ? "The $field must be a valid email." : null,
            'numeric' => ($value && !is_numeric($value)) ? "The $field must be numeric." : null,
            'integer' => ($value && !filter_var($value, FILTER_VALIDATE_INT)) ? "The $field must be an integer." : null,
            'min' => ($value && strlen($value) < (int)($params[0] ?? 0)) ? "The $field must be at least {$params[0]} characters." : null,
            'max' => ($value && strlen($value) > (int)($params[0] ?? PHP_INT_MAX)) ? "The $field must not exceed {$params[0]} characters." : null,
            'in' => ($value && !in_array($value, $params)) ? "The $field must be one of: " . implode(', ', $params) . "." : null,
            'url' => ($value && !filter_var($value, FILTER_VALIDATE_URL)) ? "The $field must be a valid URL." : null,
            'alpha' => ($value && !ctype_alpha($value)) ? "The $field must contain only letters." : null,
            'alphanumeric' => ($value && !ctype_alnum($value)) ? "The $field must contain only letters and numbers." : null,
            default => null,
        };
    }
}

/**
 * Validation exception
 */
class ValidationException extends \Exception
{
    private array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Validation failed');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toResponse(): Response
    {
        return Response::json(['errors' => $this->errors], 422);
    }
}

// ============================================================================
// Application Class
// ============================================================================

/**
 * Main application class
 */
class App
{
    private static bool $debug = false;
    private static string $basePath = '';
    private static array $config = [];

    /**
     * @var string Default config file path
     */
    private static string $configFile = '.config';

    /**
     * Create and run the application
     */
    public static function run(?string $basePath = null): void
    {
        // Set base path
        self::$basePath = $basePath ?? dirname($_SERVER['SCRIPT_FILENAME'] ?? getcwd());

        // Load configuration
        self::loadConfig();

        // Configure view system (resolve relative paths against basePath)
        $viewsPath = self::config('views.path', 'views');
        $cachePath = self::config('views.cache', 'cache');
        $viewsPath = self::resolvePath($viewsPath);
        $cachePath = self::resolvePath($cachePath);
        $engine = self::config('views.engine', 'php');
        View::configure($viewsPath, $cachePath, $engine);

        // Load routes (resolve relative path against basePath)
        $routesFile = self::config('routes.file', 'routes.php');
        $routesFile = self::resolvePath($routesFile);
        if (file_exists($routesFile)) {
            require $routesFile;
        }

        // Dispatch request and send response
        $request = new Request();
        $response = Router::dispatch($request);
        $response->send();
    }

    /**
     * Resolve a path relative to basePath if not absolute
     */
    private static function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }
        return self::$basePath . '/' . $path;
    }

    /**
     * Load configuration from .config file
     */
    private static function loadConfig(): void
    {
        $possiblePaths = [
            self::$basePath . '/' . self::$configFile,
            dirname(__DIR__) . '/' . self::$configFile,
            getcwd() . '/' . self::$configFile,
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path) && is_readable($path)) {
                self::parseConfigFile($path);
                break;
            }
        }

        // Set debug mode
        self::$debug = (bool)self::config('app.debug', false);
    }

    /**
     * Parse a configuration file
     *
     * Format:
     * [section]
     * key=value
     */
    private static function parseConfigFile(string $path): void
    {
        $contents = file_get_contents($path);
        $lines = explode("\n", $contents);
        $currentSection = 'app';

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }

            // Check for section header
            if (preg_match('/^\[([a-zA-Z_][a-zA-Z0-9_]*)\]$/', $line, $matches)) {
                $currentSection = $matches[1];
                continue;
            }

            // Parse key=value
            if (str_contains($line, '=')) {
                $equalPos = strpos($line, '=');
                $key = trim(substr($line, 0, $equalPos));
                $value = trim(substr($line, $equalPos + 1));

                // Remove quotes
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                // Convert special values
                $value = match (strtolower($value)) {
                    'true', 'yes', 'on' => true,
                    'false', 'no', 'off' => false,
                    'null' => null,
                    default => is_numeric($value) ? (str_contains($value, '.') ? (float)$value : (int)$value) : $value,
                };

                self::$config[$currentSection . '.' . $key] = $value;
            }
        }
    }

    /**
     * Set the config file path
     */
    public static function setConfigFile(string $path): void
    {
        self::$configFile = $path;
    }

    /**
     * Get a config value
     */
    public static function config(string $key, mixed $default = null): mixed
    {
        return self::$config[$key] ?? $default;
    }

    /**
     * Set a config value
     */
    public static function setConfig(string $key, mixed $value): void
    {
        self::$config[$key] = $value;
    }

    /**
     * Check if in debug mode
     */
    public static function isDebug(): bool
    {
        return self::$debug;
    }

    /**
     * Set debug mode
     */
    public static function setDebug(bool $debug): void
    {
        self::$debug = $debug;
    }

    /**
     * Get the base path
     */
    public static function basePath(string $path = ''): string
    {
        return self::$basePath . ($path ? '/' . ltrim($path, '/') : '');
    }
}

// ============================================================================
// Session Management
// ============================================================================

/**
 * Session manager with secure defaults and multiple storage drivers
 */
class Session
{
    private static bool $started = false;
    private static ?SessionDriver $driver = null;
    private static array $config = [
        'name' => 'nanomvc_session',
        'lifetime' => 7200, // 2 hours
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    /**
     * Configure session settings
     */
    public static function configure(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Set a custom session driver
     */
    public static function setDriver(SessionDriver $driver): void
    {
        self::$driver = $driver;
    }

    /**
     * Start the session
     */
    public static function start(): bool
    {
        if (self::$started) {
            return true;
        }

        if (headers_sent()) {
            return false;
        }

        // Use custom driver if set
        if (self::$driver !== null) {
            session_set_save_handler(self::$driver, true);
        }

        // Configure session
        session_name(self::$config['name']);

        $cookieParams = [
            'lifetime' => self::$config['lifetime'],
            'path' => self::$config['path'],
            'domain' => self::$config['domain'],
            'secure' => self::$config['secure'],
            'httponly' => self::$config['httponly'],
            'samesite' => self::$config['samesite'],
        ];

        session_set_cookie_params($cookieParams);

        // Set garbage collection
        ini_set('session.gc_maxlifetime', (string)self::$config['lifetime']);

        self::$started = session_start();

        // Regenerate ID periodically for security
        if (self::$started && !self::has('_last_regenerate')) {
            self::regenerate();
        } elseif (self::$started && time() - self::get('_last_regenerate', 0) > 300) {
            self::regenerate();
        }

        return self::$started;
    }

    /**
     * Regenerate session ID
     */
    public static function regenerate(bool $deleteOldSession = true): bool
    {
        if (!self::$started) {
            return false;
        }

        $result = session_regenerate_id($deleteOldSession);
        if ($result) {
            $_SESSION['_last_regenerate'] = time();
        }
        return $result;
    }

    /**
     * Get a session value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Check if a session key exists
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value
     */
    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Get all session data
     */
    public static function all(): array
    {
        self::start();
        return $_SESSION;
    }

    /**
     * Clear all session data
     */
    public static function flush(): void
    {
        self::start();
        $_SESSION = [];
    }

    /**
     * Destroy the session completely
     */
    public static function destroy(): bool
    {
        if (!self::$started) {
            return true;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        self::$started = false;
        return session_destroy();
    }

    /**
     * Flash a value for the next request only
     */
    public static function flash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Get a flashed value (and remove it)
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Check if session is started
     */
    public static function isStarted(): bool
    {
        return self::$started;
    }

    /**
     * Get session ID
     */
    public static function getId(): string
    {
        return session_id();
    }

    /**
     * Set session ID (must be called before start)
     */
    public static function setId(string $id): void
    {
        if (!self::$started) {
            session_id($id);
        }
    }

    /**
     * Get CSRF token (creates one if doesn't exist)
     */
    public static function csrfToken(): string
    {
        self::start();
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(string $token): bool
    {
        self::start();
        return isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
    }
}

/**
 * Session driver interface for custom storage backends
 */
interface SessionDriver extends \SessionHandlerInterface
{
}

/**
 * File-based session driver with better security
 */
class FileSessionDriver implements SessionDriver
{
    private string $savePath;
    private int $lifetime;

    public function __construct(string $savePath, int $lifetime = 7200)
    {
        $this->savePath = rtrim($savePath, '/');
        $this->lifetime = $lifetime;
    }

    public function open(string $path, string $name): bool
    {
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0700, true);
        }
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $file = $this->getFilePath($id);
        if (!file_exists($file)) {
            return '';
        }

        // Check if expired
        if (filemtime($file) + $this->lifetime < time()) {
            unlink($file);
            return '';
        }

        return file_get_contents($file) ?: '';
    }

    public function write(string $id, string $data): bool
    {
        $file = $this->getFilePath($id);
        return file_put_contents($file, $data, LOCK_EX) !== false;
    }

    public function destroy(string $id): bool
    {
        $file = $this->getFilePath($id);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $count = 0;
        foreach (glob($this->savePath . '/sess_*') as $file) {
            if (filemtime($file) + $max_lifetime < time()) {
                unlink($file);
                $count++;
            }
        }
        return $count;
    }

    private function getFilePath(string $id): string
    {
        return $this->savePath . '/sess_' . preg_replace('/[^a-zA-Z0-9]/', '', $id);
    }
}

// ============================================================================
// Rate Limiting Middleware
// ============================================================================

/**
 * Rate limiter with multiple storage backends
 */
class RateLimiter
{
    private static ?RateLimitStore $store = null;

    /**
     * Set the rate limit store
     */
    public static function setStore(RateLimitStore $store): void
    {
        self::$store = $store;
    }

    /**
     * Get the rate limit store (defaults to memory store)
     */
    private static function getStore(): RateLimitStore
    {
        if (self::$store === null) {
            self::$store = new MemoryRateLimitStore();
        }
        return self::$store;
    }

    /**
     * Check if a key has exceeded its rate limit
     *
     * @param string $key Unique identifier (e.g., IP address, user ID)
     * @param int $maxAttempts Maximum number of attempts allowed
     * @param int $decaySeconds Time window in seconds
     * @return bool True if within limit, false if exceeded
     */
    public static function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $store = self::getStore();
        $current = $store->get($key);

        if ($current === null) {
            $store->set($key, 1, $decaySeconds);
            return true;
        }

        if ($current >= $maxAttempts) {
            return false;
        }

        $store->increment($key);
        return true;
    }

    /**
     * Get remaining attempts for a key
     */
    public static function remaining(string $key, int $maxAttempts): int
    {
        $current = self::getStore()->get($key) ?? 0;
        return max(0, $maxAttempts - $current);
    }

    /**
     * Get seconds until the rate limit resets
     */
    public static function availableIn(string $key): int
    {
        return self::getStore()->ttl($key);
    }

    /**
     * Clear rate limit for a key
     */
    public static function clear(string $key): void
    {
        self::getStore()->forget($key);
    }

    /**
     * Get current hit count for a key
     */
    public static function hits(string $key): int
    {
        return self::getStore()->get($key) ?? 0;
    }
}

/**
 * Interface for rate limit storage backends
 */
interface RateLimitStore
{
    public function get(string $key): ?int;
    public function set(string $key, int $value, int $ttl): void;
    public function increment(string $key): int;
    public function forget(string $key): void;
    public function ttl(string $key): int;
}

/**
 * In-memory rate limit store (for single-server or testing)
 * Note: This resets on each request, so it's mainly for testing.
 * For production, use FileRateLimitStore or implement a Redis/Memcached store.
 */
class MemoryRateLimitStore implements RateLimitStore
{
    private static array $data = [];
    private static array $expiry = [];

    public function get(string $key): ?int
    {
        $this->cleanup();
        return self::$data[$key] ?? null;
    }

    public function set(string $key, int $value, int $ttl): void
    {
        self::$data[$key] = $value;
        self::$expiry[$key] = time() + $ttl;
    }

    public function increment(string $key): int
    {
        if (!isset(self::$data[$key])) {
            self::$data[$key] = 0;
        }
        return ++self::$data[$key];
    }

    public function forget(string $key): void
    {
        unset(self::$data[$key], self::$expiry[$key]);
    }

    public function ttl(string $key): int
    {
        if (!isset(self::$expiry[$key])) {
            return 0;
        }
        return max(0, self::$expiry[$key] - time());
    }

    private function cleanup(): void
    {
        $now = time();
        foreach (self::$expiry as $key => $expiry) {
            if ($expiry < $now) {
                unset(self::$data[$key], self::$expiry[$key]);
            }
        }
    }
}

/**
 * File-based rate limit store (suitable for production without Redis)
 */
class FileRateLimitStore implements RateLimitStore
{
    private string $path;

    public function __construct(string $storagePath)
    {
        $this->path = rtrim($storagePath, '/') . '/rate_limits';
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function get(string $key): ?int
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data || $data['expiry'] < time()) {
            unlink($file);
            return null;
        }

        return $data['value'];
    }

    public function set(string $key, int $value, int $ttl): void
    {
        $file = $this->getFilePath($key);
        $data = [
            'value' => $value,
            'expiry' => time() + $ttl,
        ];
        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    public function increment(string $key): int
    {
        $file = $this->getFilePath($key);
        $data = ['value' => 0, 'expiry' => time() + 60];

        if (file_exists($file)) {
            $existing = json_decode(file_get_contents($file), true);
            if ($existing && $existing['expiry'] >= time()) {
                $data = $existing;
            }
        }

        $data['value']++;
        file_put_contents($file, json_encode($data), LOCK_EX);
        return $data['value'];
    }

    public function forget(string $key): void
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function ttl(string $key): int
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return 0;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data) {
            return 0;
        }

        return max(0, $data['expiry'] - time());
    }

    private function getFilePath(string $key): string
    {
        return $this->path . '/' . md5($key) . '.json';
    }

    /**
     * Clean up expired entries
     */
    public function gc(): int
    {
        $count = 0;
        foreach (glob($this->path . '/*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data || $data['expiry'] < time()) {
                unlink($file);
                $count++;
            }
        }
        return $count;
    }
}

/**
 * Rate limiting middleware
 *
 * Usage:
 *   Router::get('/api/users', [UsersController::class, 'index'], [
 *       new ThrottleMiddleware(60, 1)  // 60 requests per minute
 *   ]);
 *
 * Or with custom key resolver:
 *   new ThrottleMiddleware(60, 1, fn($req) => $req->header('X-API-Key'))
 */
class ThrottleMiddleware
{
    private int $maxAttempts;
    private int $decayMinutes;
    private ?\Closure $keyResolver;

    public function __construct(int $maxAttempts = 60, int $decayMinutes = 1, ?\Closure $keyResolver = null)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
        $this->keyResolver = $keyResolver;
    }

    public function handle(Request $request): ?Response
    {
        $key = $this->resolveKey($request);
        $decaySeconds = $this->decayMinutes * 60;

        if (!RateLimiter::attempt($key, $this->maxAttempts, $decaySeconds)) {
            $retryAfter = RateLimiter::availableIn($key);

            return Response::json([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'Retry-After' => (string)$retryAfter,
                'X-RateLimit-Limit' => (string)$this->maxAttempts,
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string)(time() + $retryAfter),
            ]);
        }

        // Add rate limit headers to successful responses (done in response middleware)
        return null;
    }

    private function resolveKey(Request $request): string
    {
        if ($this->keyResolver !== null) {
            return 'throttle:' . ($this->keyResolver)($request);
        }

        // Default to IP address
        $ip = $request->header('X-Forwarded-For')
            ?? $request->header('X-Real-IP')
            ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        // Take first IP if multiple (from X-Forwarded-For)
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return 'throttle:' . $ip . ':' . $request->path();
    }
}

/**
 * CSRF protection middleware
 */
class CsrfMiddleware
{
    private array $exceptPaths;

    public function __construct(array $exceptPaths = [])
    {
        $this->exceptPaths = $exceptPaths;
    }

    public function handle(Request $request): ?Response
    {
        // Skip for safe methods
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return null;
        }

        // Skip for excepted paths
        foreach ($this->exceptPaths as $pattern) {
            if (fnmatch($pattern, $request->path())) {
                return null;
            }
        }

        // Get token from request
        $token = $request->input('_token')
            ?? $request->header('X-CSRF-TOKEN')
            ?? $request->header('X-XSRF-TOKEN');

        if (!$token || !Session::verifyCsrfToken($token)) {
            if ($request->expectsJson()) {
                return Response::json([
                    'error' => 'CSRF token mismatch',
                    'message' => 'The CSRF token is invalid or missing.',
                ], 419);
            }

            return Response::html(
                '<!DOCTYPE html><html><head><title>419 Page Expired</title></head>' .
                '<body><h1>419 Page Expired</h1><p>The page has expired due to inactivity. Please refresh and try again.</p></body></html>',
                419
            );
        }

        return null;
    }
}

/**
 * CORS middleware for API endpoints
 */
class CorsMiddleware
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN'],
            'exposed_headers' => [],
            'max_age' => 86400,
            'supports_credentials' => false,
        ], $config);
    }

    public function handle(Request $request): ?Response
    {
        $origin = $request->header('Origin');

        // Handle preflight OPTIONS request
        if ($request->method() === 'OPTIONS') {
            return $this->handlePreflight($request, $origin);
        }

        // For actual requests, headers will be added by the framework
        // Store CORS headers for later use
        if ($origin && $this->isAllowedOrigin($origin)) {
            $headers = $this->getCorsHeaders($origin);
            // Store in request for later retrieval
            $request->setRouteParams(array_merge(
                $request->params(),
                ['_cors_headers' => $headers]
            ));
        }

        return null;
    }

    private function handlePreflight(Request $request, ?string $origin): Response
    {
        if (!$origin || !$this->isAllowedOrigin($origin)) {
            return Response::text('', 403);
        }

        $headers = $this->getCorsHeaders($origin);
        $headers['Access-Control-Allow-Methods'] = implode(', ', $this->config['allowed_methods']);
        $headers['Access-Control-Allow-Headers'] = implode(', ', $this->config['allowed_headers']);
        $headers['Access-Control-Max-Age'] = (string)$this->config['max_age'];

        return Response::text('', 204)->withHeaders($headers);
    }

    private function getCorsHeaders(string $origin): array
    {
        $headers = [
            'Access-Control-Allow-Origin' => $this->config['allowed_origins'][0] === '*' ? '*' : $origin,
        ];

        if ($this->config['supports_credentials']) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        if (!empty($this->config['exposed_headers'])) {
            $headers['Access-Control-Expose-Headers'] = implode(', ', $this->config['exposed_headers']);
        }

        return $headers;
    }

    private function isAllowedOrigin(string $origin): bool
    {
        if (in_array('*', $this->config['allowed_origins'])) {
            return true;
        }

        return in_array($origin, $this->config['allowed_origins']);
    }
}
