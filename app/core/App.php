<?php

class App {

    public function __construct() {
        $url = $_GET['url'] ?? 'landing/index';
        $url = explode('/', trim($url, '/'));

        $controllerName = ucfirst($url[0]) . 'Controller';
        $method         = $url[1] ?? 'index';

        // Sanitize method name (only allow alphanumeric)
        $method = preg_replace('/[^a-zA-Z0-9]/', '', $method);

        $controllerPath = ROOT . "/app/Controllers/{$controllerName}.php";

        if (!file_exists($controllerPath)) {
            die("Controller not found: {$controllerName}");
        }

        require_once $controllerPath;

        if (!class_exists($controllerName)) {
            die("Controller class not found: {$controllerName}");
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $method)) {
            die("Method not found: {$method} on {$controllerName}");
        }

        $controller->$method();
    }
}