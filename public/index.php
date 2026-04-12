<?php

session_start();

define('ROOT', dirname(__DIR__));

// Show errors (VERY IMPORTANT)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load config — defines BASE_URL, DB_* constants
require_once ROOT . '/config/database.php';

// Autoload
spl_autoload_register(function ($class) {
    $paths = [
        ROOT . '/app/Controllers/',
        ROOT . '/app/models/',
        ROOT . '/app/core/'
    ];

    foreach ($paths as $path) {
        if (file_exists($path . $class . '.php')) {
            require_once $path . $class . '.php';
        }
    }
});

// Run app
require_once ROOT . '/app/core/App.php';
new App();