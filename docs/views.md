# Views and Templates

NanoMVC supports multiple template engines: native PHP, Laravel Blade, Smarty, and Twig.

## Table of Contents

- [Configuration](#configuration)
- [PHP Templates](#php-templates)
- [Blade Templates](#blade-templates)
- [Smarty Templates](#smarty-templates)
- [Twig Templates](#twig-templates)
- [Passing Data to Views](#passing-data-to-views)
- [Layouts and Inheritance](#layouts-and-inheritance)
- [Best Practices](#best-practices)

---

## Configuration

### Via .config File

```ini
[views]
engine=php       ; php, blade, smarty, or twig
path=views       ; Views directory (relative to app root)
cache=cache      ; Cache directory for compiled templates
```

### Programmatic Configuration

```php
use PaigeJulianne\NanoMVC\View;

// PHP templates (default)
View::configure(
    viewsPath: __DIR__ . '/views',
    cachePath: __DIR__ . '/cache',
    engine: 'php'
);

// Blade templates
View::configure(
    viewsPath: __DIR__ . '/views',
    cachePath: __DIR__ . '/cache/views',
    engine: 'blade'
);

// Smarty templates
View::configure(
    viewsPath: __DIR__ . '/views',
    cachePath: __DIR__ . '/cache/smarty',
    engine: 'smarty'
);

// Twig templates
View::configure(
    viewsPath: __DIR__ . '/views',
    cachePath: __DIR__ . '/cache/twig',
    engine: 'twig'
);
```

---

## PHP Templates

Native PHP templates are simple and require no compilation.

### Basic Template

```php
<!-- views/home.php -->
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title) ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($heading) ?></h1>
    <p><?= htmlspecialchars($message) ?></p>
</body>
</html>
```

### With Layout

```php
<!-- views/layout.php -->
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title ?? 'My App') ?></title>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
    </nav>

    <main>
        <?= $content ?? '' ?>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> My App</p>
    </footer>
</body>
</html>
```

```php
<!-- views/home.php -->
<?php ob_start(); ?>

<h1><?= htmlspecialchars($heading) ?></h1>
<p><?= htmlspecialchars($message) ?></p>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
```

### Loops and Conditionals

```php
<!-- views/users/index.php -->
<?php ob_start(); ?>

<h1>Users</h1>

<?php if (empty($users)): ?>
    <p>No users found.</p>
<?php else: ?>
    <ul>
        <?php foreach ($users as $user): ?>
            <li>
                <a href="/users/<?= htmlspecialchars($user['id']) ?>">
                    <?= htmlspecialchars($user['name']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
```

### Including Partials

```php
<!-- views/partials/header.php -->
<header>
    <h1><?= htmlspecialchars($siteName) ?></h1>
</header>

<!-- views/home.php -->
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
    <p>Welcome to the site!</p>
</main>
```

---

## Blade Templates

Blade provides elegant template syntax with automatic escaping.

### Installation

```bash
composer require jenssegers/blade
```

### Basic Syntax

```blade
{{-- views/home.blade.php --}}
@extends('layout')

@section('title', 'Home')

@section('content')
    <h1>{{ $heading }}</h1>
    <p>{{ $message }}</p>
@endsection
```

### Layout Template

```blade
{{-- views/layout.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>@yield('title', 'My App')</title>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer>
        @yield('footer', '&copy; ' . date('Y') . ' My App')
    </footer>
</body>
</html>
```

### Variables and Escaping

```blade
{{-- Escaped output (safe) --}}
<p>{{ $userInput }}</p>

{{-- Raw output (dangerous - only for trusted HTML) --}}
<div>{!! $trustedHtml !!}</div>

{{-- Default value --}}
<p>{{ $name ?? 'Guest' }}</p>

{{-- Ternary --}}
<p>{{ $isAdmin ? 'Admin' : 'User' }}</p>
```

### Conditionals

```blade
@if($users->isEmpty())
    <p>No users found.</p>
@elseif($users->count() === 1)
    <p>One user found.</p>
@else
    <p>{{ $users->count() }} users found.</p>
@endif

{{-- Unless (inverse if) --}}
@unless($isGuest)
    <a href="/profile">Profile</a>
@endunless

{{-- Isset --}}
@isset($record)
    <p>Record exists</p>
@endisset

{{-- Empty --}}
@empty($records)
    <p>No records</p>
@endempty
```

### Loops

```blade
@foreach($users as $user)
    <p>{{ $user->name }}</p>
@endforeach

{{-- With empty fallback --}}
@forelse($users as $user)
    <p>{{ $user->name }}</p>
@empty
    <p>No users found.</p>
@endforelse

{{-- Loop variable --}}
@foreach($users as $user)
    @if($loop->first)
        <p>First user!</p>
    @endif

    <p>{{ $loop->iteration }}. {{ $user->name }}</p>

    @if($loop->last)
        <p>Last user!</p>
    @endif
@endforeach
```

### Including Partials

```blade
{{-- Simple include --}}
@include('partials.header')

{{-- Include with data --}}
@include('partials.user-card', ['user' => $user])

{{-- Include if exists --}}
@includeIf('partials.optional')

{{-- Include when condition is true --}}
@includeWhen($isAdmin, 'partials.admin-panel')
```

### Components

```blade
{{-- views/components/alert.blade.php --}}
<div class="alert alert-{{ $type ?? 'info' }}">
    {{ $slot }}
</div>

{{-- Usage --}}
@component('components.alert', ['type' => 'success'])
    Operation completed successfully!
@endcomponent
```

---

## Smarty Templates

Smarty offers a designer-friendly template syntax.

### Installation

```bash
composer require smarty/smarty
```

### Basic Syntax

```smarty
{* views/home.tpl *}
{extends file="layout.tpl"}

{block name="title"}Home{/block}

{block name="content"}
    <h1>{$heading|escape}</h1>
    <p>{$message|escape}</p>
{/block}
```

### Layout Template

```smarty
{* views/layout.tpl *}
<!DOCTYPE html>
<html>
<head>
    <title>{block name="title"}My App{/block}</title>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
    </nav>

    <main>
        {block name="content"}{/block}
    </main>

    <footer>
        {block name="footer"}&copy; {$smarty.now|date_format:"%Y"} My App{/block}
    </footer>
</body>
</html>
```

### Variables and Modifiers

```smarty
{* Basic output *}
<p>{$name}</p>

{* With escape modifier (recommended) *}
<p>{$userInput|escape}</p>

{* Default value *}
<p>{$name|default:"Guest"}</p>

{* Chained modifiers *}
<p>{$title|escape|upper}</p>

{* Common modifiers *}
{$text|truncate:50:"..."}
{$price|number_format:2}
{$date|date_format:"%B %e, %Y"}
{$name|capitalize}
{$text|strip_tags}
```

### Conditionals

```smarty
{if $users|@count == 0}
    <p>No users found.</p>
{elseif $users|@count == 1}
    <p>One user found.</p>
{else}
    <p>{$users|@count} users found.</p>
{/if}
```

### Loops

```smarty
{foreach $users as $user}
    <p>{$user.name|escape}</p>
{foreachelse}
    <p>No users found.</p>
{/foreach}

{* With index *}
{foreach $users as $index => $user}
    <p>{$index + 1}. {$user.name|escape}</p>
{/foreach}

{* Loop properties *}
{foreach $users as $user}
    {if $user@first}<ul>{/if}
    <li>{$user.name|escape}</li>
    {if $user@last}</ul>{/if}
{/foreach}
```

### Including Templates

```smarty
{* Simple include *}
{include file="partials/header.tpl"}

{* Include with variables *}
{include file="partials/user-card.tpl" user=$currentUser}

{* Assign include output to variable *}
{include file="partials/sidebar.tpl" assign="sidebar"}
{$sidebar}
```

---

## Twig Templates

Twig provides a modern, flexible template syntax with automatic escaping.

### Installation

```bash
composer require twig/twig
```

### Basic Syntax

```twig
{# views/home.twig #}
{% extends "layout.twig" %}

{% block title %}Home{% endblock %}

{% block content %}
    <h1>{{ heading }}</h1>
    <p>{{ message }}</p>
{% endblock %}
```

### Layout Template

```twig
{# views/layout.twig #}
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}My App{% endblock %}</title>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
    </nav>

    <main>
        {% block content %}{% endblock %}
    </main>

    <footer>
        {% block footer %}&copy; {{ "now"|date("Y") }} My App{% endblock %}
    </footer>
</body>
</html>
```

### Variables and Filters

```twig
{# Basic output (auto-escaped) #}
<p>{{ name }}</p>

{# Raw output (dangerous - only for trusted HTML) #}
<div>{{ trustedHtml|raw }}</div>

{# Default value #}
<p>{{ name|default("Guest") }}</p>

{# Chained filters #}
<p>{{ title|escape|upper }}</p>

{# Common filters #}
{{ text|truncate(50) }}
{{ price|number_format(2) }}
{{ date|date("F j, Y") }}
{{ name|capitalize }}
{{ text|striptags }}
{{ text|length }}
```

### Conditionals

```twig
{% if users is empty %}
    <p>No users found.</p>
{% elseif users|length == 1 %}
    <p>One user found.</p>
{% else %}
    <p>{{ users|length }} users found.</p>
{% endif %}

{# Ternary #}
{{ isAdmin ? 'Admin' : 'User' }}

{# Null coalescing #}
{{ user.name ?? 'Anonymous' }}

{# Defined check #}
{% if user is defined %}
    <p>User exists</p>
{% endif %}
```

### Loops

```twig
{% for user in users %}
    <p>{{ user.name }}</p>
{% else %}
    <p>No users found.</p>
{% endfor %}

{# With index #}
{% for user in users %}
    <p>{{ loop.index }}. {{ user.name }}</p>
{% endfor %}

{# Loop properties #}
{% for user in users %}
    {% if loop.first %}<ul>{% endif %}
    <li>{{ user.name }}</li>
    {% if loop.last %}</ul>{% endif %}
{% endfor %}

{# Available loop variables #}
{# loop.index - 1-indexed iteration #}
{# loop.index0 - 0-indexed iteration #}
{# loop.first - true if first iteration #}
{# loop.last - true if last iteration #}
{# loop.length - total number of items #}
```

### Including Templates

```twig
{# Simple include #}
{% include "partials/header.twig" %}

{# Include with variables #}
{% include "partials/user-card.twig" with {'user': currentUser} %}

{# Include if exists #}
{% include "partials/optional.twig" ignore missing %}

{# Include with only specific variables #}
{% include "partials/sidebar.twig" with {'items': menuItems} only %}
```

### Macros (Reusable Components)

```twig
{# Define macro #}
{% macro input(name, value, type = "text") %}
    <input type="{{ type }}" name="{{ name }}" value="{{ value }}">
{% endmacro %}

{# Import and use #}
{% import _self as forms %}
{{ forms.input('email', '', 'email') }}

{# From external file #}
{% import "macros/forms.twig" as forms %}
{{ forms.input('username', user.name) }}
```

---

## Passing Data to Views

### From Controller

```php
class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('home', [
            'title' => 'Welcome',
            'heading' => 'Hello, World!',
            'message' => 'Welcome to NanoMVC',
            'users' => User::all(),
            'isAdmin' => Session::get('is_admin', false)
        ]);
    }
}
```

### Using View::make() Directly

```php
use PaigeJulianne\NanoMVC\View;

// In controller or route handler
return View::make('users.show', [
    'user' => $user,
    'posts' => $user->posts()
]);

// With custom status code
return View::make('errors.not-found', [
    'message' => 'Page not found'
], 404);
```

### Sharing Data Globally

```php
// In bootstrap or middleware
View::share('appName', 'My Application');
View::share('currentYear', date('Y'));

// Now available in all views
// PHP: <?= $appName ?>
// Blade: {{ $appName }}
// Smarty: {$appName}
// Twig: {{ appName }}
```

---

## Layouts and Inheritance

### PHP Layout Pattern

```php
<!-- views/layouts/app.php -->
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title ?? 'App') ?></title>
    <?= $headContent ?? '' ?>
</head>
<body>
    <?php include __DIR__ . '/../partials/nav.php'; ?>

    <main class="container">
        <?= $content ?>
    </main>

    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <?= $scripts ?? '' ?>
</body>
</html>

<!-- views/pages/dashboard.php -->
<?php
$title = 'Dashboard';
ob_start();
?>
<style>.dashboard { padding: 20px; }</style>
<?php $headContent = ob_get_clean(); ?>

<?php ob_start(); ?>
<div class="dashboard">
    <h1>Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($user->name) ?></p>
</div>
<?php $content = ob_get_clean(); ?>

<?php ob_start(); ?>
<script>console.log('Dashboard loaded');</script>
<?php $scripts = ob_get_clean(); ?>

<?php include __DIR__ . '/../layouts/app.php'; ?>
```

### Blade Inheritance

```blade
{{-- views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>@yield('title', 'App')</title>
    @stack('styles')
</head>
<body>
    @include('partials.nav')

    <main class="container">
        @yield('content')
    </main>

    @include('partials.footer')

    @stack('scripts')
</body>
</html>

{{-- views/pages/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>.dashboard { padding: 20px; }</style>
@endpush

@section('content')
<div class="dashboard">
    <h1>Dashboard</h1>
    <p>Welcome, {{ $user->name }}</p>
</div>
@endsection

@push('scripts')
<script>console.log('Dashboard loaded');</script>
@endpush
```

### Smarty Inheritance

```smarty
{* views/layouts/app.tpl *}
<!DOCTYPE html>
<html>
<head>
    <title>{block name="title"}App{/block}</title>
    {block name="styles"}{/block}
</head>
<body>
    {include file="partials/nav.tpl"}

    <main class="container">
        {block name="content"}{/block}
    </main>

    {include file="partials/footer.tpl"}

    {block name="scripts"}{/block}
</body>
</html>

{* views/pages/dashboard.tpl *}
{extends file="layouts/app.tpl"}

{block name="title"}Dashboard{/block}

{block name="styles"}
<style>.dashboard { padding: 20px; }</style>
{/block}

{block name="content"}
<div class="dashboard">
    <h1>Dashboard</h1>
    <p>Welcome, {$user->name|escape}</p>
</div>
{/block}

{block name="scripts"}
<script>console.log('Dashboard loaded');</script>
{/block}
```

### Twig Inheritance

```twig
{# views/layouts/app.twig #}
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}App{% endblock %}</title>
    {% block styles %}{% endblock %}
</head>
<body>
    {% include "partials/nav.twig" %}

    <main class="container">
        {% block content %}{% endblock %}
    </main>

    {% include "partials/footer.twig" %}

    {% block scripts %}{% endblock %}
</body>
</html>

{# views/pages/dashboard.twig #}
{% extends "layouts/app.twig" %}

{% block title %}Dashboard{% endblock %}

{% block styles %}
<style>.dashboard { padding: 20px; }</style>
{% endblock %}

{% block content %}
<div class="dashboard">
    <h1>Dashboard</h1>
    <p>Welcome, {{ user.name }}</p>
</div>
{% endblock %}

{% block scripts %}
<script>console.log('Dashboard loaded');</script>
{% endblock %}
```

---

## Best Practices

### 1. Always Escape Output

```php
// PHP - ALWAYS escape user data
<?= htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8') ?>

// Blade - automatic escaping with {{ }}
{{ $userInput }}

// Smarty - use escape modifier
{$userInput|escape}

// Twig - automatic escaping with {{ }}
{{ userInput }}
```

### 2. Use Layouts for Consistency

Avoid duplicating HTML structure across templates.

### 3. Keep Logic Minimal

```php
// BAD: Complex logic in view
<?php
$users = User::where('active', true)->orderBy('name')->get();
foreach ($users as $user) {
    if ($user->hasPermission('admin')) {
        // ...
    }
}
?>

// GOOD: Logic in controller, simple display in view
<?php foreach ($activeUsers as $user): ?>
    <p><?= htmlspecialchars($user['name']) ?></p>
<?php endforeach; ?>
```

### 4. Use Partials for Reusable Components

```
views/
├── layouts/
│   └── app.php
├── partials/
│   ├── nav.php
│   ├── footer.php
│   ├── flash-messages.php
│   └── user-card.php
├── users/
│   ├── index.php
│   └── show.php
└── home.php
```

### 5. Cache Templates in Production

Blade and Smarty compile templates automatically. Ensure cache directory is writable:

```bash
chmod 755 cache/
chmod 755 cache/views/
```

---

## Next Steps

- [Controllers](controllers.md) - View rendering from controllers
- [Request & Response](request-response.md) - HTTP handling
- [Configuration](configuration.md) - Template engine settings
