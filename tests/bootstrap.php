<?php

/**
 * PHPUnit bootstrap file for NanoMVC tests
 */

// Load the framework
require_once __DIR__ . '/../NanoMVC.php';

// Set up test environment
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
