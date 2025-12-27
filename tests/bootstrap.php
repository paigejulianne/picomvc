<?php

/**
 * PHPUnit bootstrap file for PicoMVC tests
 */

// Load the framework
require_once __DIR__ . '/../PicoMVC.php';

// Set up test environment
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
