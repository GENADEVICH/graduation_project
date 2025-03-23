<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

session_start();

// Подключение общих файлов
require 'includes/db.php';
require 'includes/functions.php';

// Определение базовых путей
define('BASE_PATH', __DIR__);
define('PAGES_PATH', BASE_PATH . '/pages');

// Маршруты
$routes = [
    'home' => 'home.php',
    'product' => 'product.php',
    'cart' => 'cart.php',
    'checkout' => 'checkout.php',
    'login' => 'login.php',
    'register' => 'register.php',
];

// Определение текущей страницы
$page = $_GET['page'] ?? 'home';

// Проверка маршрута
if (array_key_exists($page, $routes)) {
    require PAGES_PATH . '/' . $routes[$page];
} else {
    error_log("Попытка доступа к несуществующей странице: {$page}");
    require PAGES_PATH . '/error.php';
}