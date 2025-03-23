<?php
// includes/router.php

class Router {
    private $routes = [];

    public function addRoute($path, $file) {
        $this->routes[$path] = $file;
    }

    public function handleRequest() {
        $requestUri = strtok($_SERVER['REQUEST_URI'], '?'); // Получаем текущий URI

        if (isset($this->routes[$requestUri])) {
            require $this->routes[$requestUri];
        } else {
            echo "404 Not Found";
        }
    }
}
?>