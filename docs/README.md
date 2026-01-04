# NanoMVC Documentation

Welcome to the NanoMVC documentation. NanoMVC is a lightweight, high-performance MVC framework for PHP 8.0+ designed for building web applications of any scale.

## Table of Contents

### Getting Started
- [Installation](installation.md) - Installing and setting up NanoMVC
- [Quick Start](quick-start.md) - Build your first application
- [Configuration](configuration.md) - Configuration options and settings

### Core Concepts
- [Routing](routing.md) - URL routing with parameters and groups
- [Controllers](controllers.md) - Handling requests and responses
- [Views & Templates](views.md) - Template engines and rendering
- [Request & Response](request-response.md) - HTTP request/response handling

### Features
- [Validation](validation.md) - Input validation rules
- [Sessions](sessions.md) - Session management and flash messages
- [Middleware](middleware.md) - Request/response middleware
- [Rate Limiting](rate-limiting.md) - API throttling and rate limits

### Advanced Topics
- [Performance](performance.md) - Route caching, compression, optimization
- [Security](security.md) - CSRF protection, CORS, best practices
- [Database Integration](database.md) - Using NanoORM with NanoMVC

### Reference
- [API Reference](api-reference.md) - Complete API documentation

---

## Overview

NanoMVC provides:

- **Single-file framework** - Easy to deploy and version control
- **Enterprise-scale performance** - O(1) route lookup, route caching
- **Multiple template engines** - PHP, Blade, and Smarty support
- **Built-in security** - CSRF protection, rate limiting, secure sessions
- **Zero dependencies** - Only requires PHP 8.0+

## Requirements

- PHP 8.0 or higher
- Apache with mod_rewrite or Nginx
- Composer (recommended)

## Quick Example

```php
<?php
require 'vendor/autoload.php';

use PaigeJulianne\NanoMVC\{App, Router, Controller, Request, Response};

// Define a route
Router::get('/', function() {
    return 'Hello, World!';
});

Router::get('/users/{id}', function(Request $request) {
    return 'User ID: ' . $request->param('id');
});

// Run the application
App::run(__DIR__);
```

## License

NanoMVC is released under the [GPL-3.0-or-later](../LICENSE) license.

Copyright 2024-present Paige Julianne Sullivan
