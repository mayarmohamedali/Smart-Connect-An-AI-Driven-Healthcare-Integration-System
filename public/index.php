<?php

session_start();

define('ROOT', dirname(__DIR__));

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load config — defines BASE_URL, DB_* constants
require_once ROOT . '/config/database.php';

// Autoload: controllers + all model files (including PolicyAutoSetup)
spl_autoload_register(function ($class) {
    $paths = [
        ROOT . '/app/Controllers/',
        ROOT . '/app/models/',
        ROOT . '/app/core/',
    ];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Run app
require_once ROOT . '/app/core/App.php';
new App();