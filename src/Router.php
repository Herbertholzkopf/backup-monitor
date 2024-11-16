<?php
// src/Router.php
namespace App;

class Router {
    private $routes = [];
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function addRoute($method, $path, $controller, $action) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path if exists
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/') {
            $path = substr($path, strlen($basePath));
        }

        foreach ($this->routes as $route) {
            $pattern = $this->convertRouteToRegex($route['path']);
            
            if ($route['method'] === $method && preg_match($pattern, $path, $matches)) {
                array_shift($matches); // Remove full match
                
                $controllerName = "App\\Controllers\\" . $route['controller'];
                $controller = new $controllerName($this->db);
                
                return call_user_func_array([$controller, $route['action']], $matches);
            }
        }

        // 404 if no route matches
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Route not found'
        ]);
    }

    private function convertRouteToRegex($route) {
        return '#^' . preg_replace('#\{([a-zA-Z0-9_]+)\}#', '([^/]+)', $route) . '$#';
    }
}