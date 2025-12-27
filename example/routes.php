<?php

/**
 * PicoMVC Routes
 *
 * Define your application routes here.
 */

use PaigeJulianne\PicoMVC\Router;

// Include controllers
require_once __DIR__ . '/controllers/HomeController.php';
require_once __DIR__ . '/controllers/UsersController.php';

// Home routes
Router::get('/', [HomeController::class, 'index']);
Router::get('/about', [HomeController::class, 'about']);

// User routes
Router::get('/users', [UsersController::class, 'index']);
Router::get('/users/{id}', [UsersController::class, 'show']);
Router::post('/users', [UsersController::class, 'store']);
Router::put('/users/{id}', [UsersController::class, 'update']);
Router::delete('/users/{id}', [UsersController::class, 'destroy']);

// API routes group
Router::group(['prefix' => 'api'], function () {
    Router::get('/users', [UsersController::class, 'apiIndex']);
    Router::get('/users/{id}', [UsersController::class, 'apiShow']);
});

// Custom 404 handler
Router::setNotFoundHandler(function () {
    return \PaigeJulianne\PicoMVC\View::make('errors.404', [], 404);
});
