<?php

/**
 * PicoMVC Blade Example Routes
 */

use PaigeJulianne\PicoMVC\Router;

require_once __DIR__ . '/controllers/HomeController.php';
require_once __DIR__ . '/controllers/UsersController.php';

// Home routes
Router::get('/', [HomeController::class, 'index']);
Router::get('/about', [HomeController::class, 'about']);

// User routes
Router::get('/users', [UsersController::class, 'index']);
Router::get('/users/{id}', [UsersController::class, 'show']);

// Custom 404 handler
Router::setNotFoundHandler(function () {
    return \PaigeJulianne\PicoMVC\View::make('errors.404', [], 404);
});
