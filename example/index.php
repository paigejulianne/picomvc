<?php

/**
 * PicoMVC Example Application
 *
 * This is the entry point for your application.
 * All requests should be routed through this file.
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PaigeJulianne\PicoMVC\App;

// Run the application
App::run(__DIR__);
