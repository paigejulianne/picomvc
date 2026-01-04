# Configuration

NanoMVC can be configured via a `.config` file or programmatically.

## Table of Contents

- [Configuration File](#configuration-file)
- [App Settings](#app-settings)
- [View Settings](#view-settings)
- [Route Settings](#route-settings)
- [Database Settings](#database-settings)
- [Session Settings](#session-settings)
- [Programmatic Configuration](#programmatic-configuration)
- [Environment Variables](#environment-variables)

---

## Configuration File

Create a `.config` file in your application root:

```ini
[app]
name=My Application
debug=true
timezone=America/New_York
base_url=

[views]
engine=php
path=views
cache=cache

[routes]
file=routes.php

[database]
driver=mysql
host=localhost
port=3306
name=myapp
user=root
password=
charset=utf8mb4

[session]
name=myapp_session
lifetime=7200
secure=false
httponly=true
samesite=Lax
```

---

## App Settings

### name

Application name used in templates and meta tags.

```ini
[app]
name=My Application
```

```php
$appName = App::config('app.name', 'Default App');
```

### debug

Enable or disable debug mode. In debug mode:
- Detailed error messages are shown
- JSON responses include pretty printing
- Stack traces are displayed

```ini
[app]
debug=true   ; Development
debug=false  ; Production
```

```php
if (App::isDebug()) {
    // Development only code
}
```

### timezone

Set the default timezone for date/time functions.

```ini
[app]
timezone=UTC
timezone=America/New_York
timezone=Europe/London
```

### base_url

Base URL for the application (useful for subdirectory installations).

```ini
[app]
base_url=/myapp
```

---

## View Settings

### engine

Template engine to use: `php`, `blade`, or `smarty`.

```ini
[views]
engine=php     ; Native PHP templates
engine=blade   ; Laravel Blade
engine=smarty  ; Smarty templates
```

### path

Path to views directory (relative to application root).

```ini
[views]
path=views
path=resources/views
path=app/templates
```

### cache

Path to template cache directory.

```ini
[views]
cache=cache
cache=storage/cache/views
```

---

## Route Settings

### file

Path to the routes file (relative to application root).

```ini
[routes]
file=routes.php
file=config/routes.php
file=app/routes/web.php
```

---

## Database Settings

These settings are used with NanoORM integration.

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

### Driver Options

```ini
driver=mysql    ; MySQL/MariaDB
driver=pgsql    ; PostgreSQL
driver=sqlite   ; SQLite
```

### SQLite Configuration

```ini
[database]
driver=sqlite
path=database/app.db
```

---

## Session Settings

### name

Session cookie name.

```ini
[session]
name=myapp_session
```

### lifetime

Session lifetime in seconds.

```ini
[session]
lifetime=7200     ; 2 hours
lifetime=86400    ; 24 hours
lifetime=604800   ; 1 week
```

### secure

Only send cookie over HTTPS.

```ini
[session]
secure=true   ; Production (HTTPS)
secure=false  ; Development (HTTP)
```

### httponly

Prevent JavaScript access to session cookie.

```ini
[session]
httponly=true   ; Recommended
```

### samesite

CSRF protection via SameSite cookie attribute.

```ini
[session]
samesite=Lax     ; Default, good balance
samesite=Strict  ; More secure, may break some flows
samesite=None    ; Required for cross-site (needs secure=true)
```

---

## Programmatic Configuration

### App Configuration

```php
use PaigeJulianne\NanoMVC\App;

// Load config file
App::loadConfig(__DIR__ . '/.config');

// Get config values
$appName = App::config('app.name');
$debug = App::config('app.debug', false);
$dbHost = App::config('database.host', 'localhost');

// Check debug mode
if (App::isDebug()) {
    // Development mode
}
```

### View Configuration

```php
use PaigeJulianne\NanoMVC\View;

View::configure(
    viewsPath: __DIR__ . '/views',
    cachePath: __DIR__ . '/cache',
    engine: 'blade'
);
```

### Session Configuration

```php
use PaigeJulianne\NanoMVC\Session;

Session::configure([
    'name' => 'myapp_session',
    'lifetime' => 7200,
    'path' => '/',
    'domain' => '.example.com',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
```

### Response Configuration

```php
use PaigeJulianne\NanoMVC\Response;

// Configure compression
Response::configureCompression(
    threshold: 1024,  // Minimum bytes
    level: 6          // Compression level (0-9)
);
```

### Request Configuration

```php
use PaigeJulianne\NanoMVC\Request;

// Set max body size (for uploads)
Request::setMaxBodySize(10 * 1024 * 1024);  // 10MB
```

---

## Environment Variables

Use environment variables for sensitive configuration.

### Setting Environment Variables

```bash
# In shell
export DB_PASSWORD=secret
export APP_DEBUG=false

# Or in .env file (using phpdotenv)
DB_PASSWORD=secret
APP_DEBUG=false
```

### Using Environment Variables

```php
// In your bootstrap
$dbPassword = getenv('DB_PASSWORD');
$debug = getenv('APP_DEBUG') === 'true';

// Or with default
$apiKey = getenv('API_KEY') ?: 'default-key';
```

### Config with Environment Variables

```php
// config/database.php
return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: 3306,
    'name' => getenv('DB_NAME') ?: 'app',
    'user' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
];
```

### Using phpdotenv

```bash
composer require vlucas/phpdotenv
```

```php
// In index.php or bootstrap
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Now use $_ENV or getenv()
$debug = $_ENV['APP_DEBUG'] ?? 'false';
```

### Example .env File

```ini
# Application
APP_NAME="My Application"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=myapp
DB_USER=root
DB_PASSWORD=

# Session
SESSION_LIFETIME=7200
SESSION_SECURE=false

# External Services
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USER=
MAIL_PASSWORD=

API_KEY=
```

---

## Configuration for Different Environments

### Development Configuration

```ini
; .config.development
[app]
name=My App (Dev)
debug=true

[database]
host=localhost
name=myapp_dev
user=root
password=

[session]
secure=false
```

### Production Configuration

```ini
; .config.production
[app]
name=My App
debug=false

[database]
host=db.example.com
name=myapp_prod
user=appuser
password=${DB_PASSWORD}

[session]
secure=true
samesite=Strict
```

### Loading Based on Environment

```php
// index.php
$env = getenv('APP_ENV') ?: 'development';
$configFile = __DIR__ . "/.config.{$env}";

if (file_exists($configFile)) {
    App::loadConfig($configFile);
} else {
    App::loadConfig(__DIR__ . '/.config');
}

App::run(__DIR__);
```

---

## Complete Example

```php
<?php
// index.php

require_once 'vendor/autoload.php';

use PaigeJulianne\NanoMVC\{App, View, Session, Response, Request};

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Determine environment
$env = $_ENV['APP_ENV'] ?? 'development';
$isProduction = $env === 'production';

// Load appropriate config
$configFile = __DIR__ . "/.config.{$env}";
App::loadConfig(file_exists($configFile) ? $configFile : __DIR__ . '/.config');

// Configure components
View::configure(
    viewsPath: __DIR__ . '/views',
    cachePath: __DIR__ . '/cache',
    engine: App::config('views.engine', 'php')
);

Session::configure([
    'name' => App::config('session.name', 'app_session'),
    'lifetime' => (int) App::config('session.lifetime', 7200),
    'secure' => $isProduction,
    'httponly' => true,
    'samesite' => 'Lax'
]);

Response::configureCompression(
    threshold: 1024,
    level: $isProduction ? 6 : 0
);

Request::setMaxBodySize(10 * 1024 * 1024);

// Run application
App::run(__DIR__);
```

---

## Next Steps

- [Installation](installation.md) - Initial setup
- [Performance](performance.md) - Performance tuning
- [Security](security.md) - Security settings
