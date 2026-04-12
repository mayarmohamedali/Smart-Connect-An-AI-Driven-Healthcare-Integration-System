<?php

class App {

    public function __construct() {

        // Default route
        $url = $_GET['url'] ?? 'auth/login';
        $url = explode('/', $url);

        $controllerName = ucfirst($url[0]) . "Controller";
        $method = $url[1] ?? 'index';

        $controllerPath = ROOT . "/app/Controllers/$controllerName.php";

        if (!file_exists($controllerPath)) {
            die("Controller not found: " . $controllerName);
        }

        require_once $controllerPath;

        $controller = new $controllerName();

        if (!method_exists($controller, $method)) {
            die("Method not found: " . $method);
        }

        $controller->$method();
    }
}