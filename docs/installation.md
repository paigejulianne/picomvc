# Installation

This guide covers installing NanoMVC and setting up your development environment.

## Table of Contents

- [Requirements](#requirements)
- [Installation Methods](#installation-methods)
- [Web Server Configuration](#web-server-configuration)
- [Directory Structure](#directory-structure)
- [Verifying Installation](#verifying-installation)

---

## Requirements

### Minimum Requirements

- **PHP 8.0** or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Composer** (recommended for dependency management)

### PHP Extensions

Required extensions (usually enabled by default):
- `json` - JSON encoding/decoding
- `mbstring` - Multibyte string handling (recommended)
- `zlib` - Required for gzip compression (optional)

### Optional Requirements

For template engines:
- **Blade**: `jenssegers/blade` package
- **Smarty**: `smarty/smarty` package

For database operations:
- **NanoORM**: `paigejulianne/nanoorm` package

---

## Installation Methods

### Via Composer (Recommended)

```bash
composer require paigejulianne/nanomvc
```

This will install NanoMVC and its dependencies in your `vendor` directory.

### Manual Installation

1. Download the latest release from [GitHub](https://github.com/paigejulianne/nanomvc/releases)

2. Copy `NanoMVC.php` to your project:

```bash
cp NanoMVC.php /path/to/your/project/
```

3. Include it in your application:

```php
<?php
require_once 'NanoMVC.php';

use PaigeJulianne\NanoMVC\App;
App::run(__DIR__);
```

### Installing Optional Packages

**For Blade templates:**
```bash
composer require jenssegers/blade
```

**For Smarty templates:**
```bash
composer require smarty/smarty
```

**For database operations:**
```bash
composer require paigejulianne/nanoorm
```

---

## Web Server Configuration

### Apache Configuration

#### Basic .htaccess

Create `.htaccess` in your application's public directory:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect to index.php if not a file or directory
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule .* index.php [L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# Prevent access to sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
```

#### Enabling mod_rewrite

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Apache Virtual Host

```apache
<VirtualHost *:80>
    ServerName myapp.local
    DocumentRoot /var/www/myapp/public

    <Directory /var/www/myapp/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/myapp_error.log
    CustomLog ${APACHE_LOG_DIR}/myapp_access.log combined
</VirtualHost>
```

#### User Directories (~username)

For development in user directories, edit `/etc/apache2/mods-available/userdir.conf`:

```apache
<Directory /home/*/public_html>
    AllowOverride All
    Options Indexes FollowSymLinks
    Require all granted
</Directory>
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name myapp.local;
    root /var/www/myapp/public;
    index index.php;

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Handle all requests through index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # Security
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Deny access to sensitive files
    location ~ \.(config|md|json|lock)$ {
        deny all;
    }
}
```

---

## Directory Structure

### Recommended Structure

```
myapp/
├── public/                 # Web root (DocumentRoot)
│   ├── .htaccess          # Apache rewrite rules
│   ├── index.php          # Application entry point
│   └── assets/            # CSS, JS, images
│       ├── css/
│       ├── js/
│       └── images/
├── app/
│   ├── controllers/       # Controller classes
│   ├── models/            # Model classes (NanoORM)
│   ├── middleware/        # Custom middleware
│   └── helpers/           # Helper functions
├── views/                 # Template files
│   ├── layouts/
│   ├── partials/
│   └── errors/
├── storage/               # Application storage
│   ├── cache/             # Template cache
│   ├── sessions/          # Session files
│   └── logs/              # Application logs
├── config/
│   └── .config            # Configuration file
├── routes/
│   └── web.php            # Route definitions
├── vendor/                # Composer dependencies
├── composer.json
└── README.md
```

### Minimal Structure

For simple applications:

```
myapp/
├── .htaccess
├── .config
├── index.php
├── routes.php
├── controllers/
│   └── HomeController.php
├── views/
│   └── home.php
└── cache/
```

---

## Verifying Installation

### 1. Create Entry Point

Create `public/index.php` (or `index.php` for minimal setup):

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PaigeJulianne\NanoMVC\{App, Router};

// Test route
Router::get('/', function() {
    return '<h1>NanoMVC is working!</h1>';
});

Router::get('/info', function() {
    return [
        'php_version' => PHP_VERSION,
        'nanomvc' => 'v1.0.0',
        'extensions' => get_loaded_extensions()
    ];
});

App::run(__DIR__);
```

### 2. Test in Browser

Visit your application:
- `http://localhost/myapp/` - Should display "NanoMVC is working!"
- `http://localhost/myapp/info` - Should return JSON with system info

### 3. Check Requirements

Create a requirements check script:

```php
<?php
echo "<h2>NanoMVC Requirements Check</h2>";

// PHP Version
$phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
echo "<p>PHP Version: " . PHP_VERSION . " " . ($phpOk ? "✓" : "✗") . "</p>";

// Extensions
$extensions = ['json', 'mbstring', 'zlib'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "<p>Extension $ext: " . ($loaded ? "✓" : "✗ (optional)") . "</p>";
}

// Write permissions
$dirs = ['cache', 'storage/sessions'];
foreach ($dirs as $dir) {
    $writable = is_writable(__DIR__ . '/' . $dir);
    echo "<p>Directory $dir writable: " . ($writable ? "✓" : "✗") . "</p>";
}
```

---

## Troubleshooting

### Common Issues

#### 404 Error on All Routes

1. Check mod_rewrite is enabled:
   ```bash
   sudo a2enmod rewrite
   ```

2. Verify AllowOverride is set:
   ```apache
   <Directory /path/to/app>
       AllowOverride All
   </Directory>
   ```

3. Check .htaccess permissions:
   ```bash
   chmod 644 .htaccess
   ```

#### 500 Internal Server Error

1. Check PHP error logs:
   ```bash
   tail -f /var/log/apache2/error.log
   ```

2. Enable debug mode in `.config`:
   ```ini
   [app]
   debug=true
   ```

3. Verify PHP syntax:
   ```bash
   php -l index.php
   ```

#### Permission Denied Errors

Set proper permissions:
```bash
# Directories
find /path/to/app -type d -exec chmod 755 {} \;

# Files
find /path/to/app -type f -exec chmod 644 {} \;

# Cache directories need write access
chmod 775 cache storage/sessions storage/logs
```

---

## Next Steps

- [Quick Start Guide](quick-start.md) - Build your first application
- [Configuration](configuration.md) - Configure your application
- [Routing](routing.md) - Learn about URL routing
