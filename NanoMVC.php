<?php

namespace PaigeJulianne\NanoMVC;

/**
 * Package NanoMVC
 *
 * A lightweight MVC framework for PHP 8.0+ with support for Blade and Smarty templates.
 *
 * @author    Paige Julianne Sullivan <paige@paigejulianne.com> https://paigejulianne.com
 * @copyright 2024-present Paige Julianne Sullivan
 * @license   GPL-3.0-or-later
 * @link      https://github.com/paigejulianne/nanomvc
 * @version   1.0.0
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
     * Parse request headers from $_SERVER
     */
    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
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
            } elseif (isset($this->headers['X-HTTP-METHOD-OVERRIDE'])) {
                $method = strtoupper($this->headers['X-HTTP-METHOD-OVERRIDE']);
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
     */
    public function header(string $name, mixed $default = null): mixed
    {
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
     * Get raw request body
     */
    public function getContent(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    /**
     * Get JSON decoded body
     */
    public function json(): array
    {
        $content = $this->getContent();
        return json_decode($content, true) ?: [];
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
 * Simple HTTP response wrapper
 */
class Response
{
    private string $content = '';
    private int $statusCode = 200;
    private array $headers = [];

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
     * Send the response
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        echo $this->content;
    }

    /**
     * Create a JSON response
     */
    public static function json(mixed $data, int $status = 200): self
    {
        $response = new self();
        $response->setStatusCode($status);
        $response->header('Content-Type', 'application/json');
        $response->setContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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
 * Simple router with parameter support
 */
class Router
{
    /**
     * @var array Registered routes grouped by HTTP method
     */
    private static array $routes = [];

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
     * Add a route to the routing table
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

        // Convert path to regex pattern
        $pattern = self::pathToRegex($path);

        self::$routes[$method][] = [
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
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
     * Dispatch the request to the appropriate handler
     */
    public static function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        // Normalize path
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        // Find matching route
        $routes = self::$routes[$method] ?? [];

        foreach ($routes as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->setRouteParams($params);

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
        }

        // No route found
        return self::handleNotFound($request);
    }

    /**
     * Run a middleware
     */
    private static function runMiddleware(string|callable $middleware, Request $request): ?Response
    {
        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
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
        self::$groupPrefix = null;
        self::$groupMiddleware = [];
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
