<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Подключение общих файлов
require 'includes/db.php';
require 'includes/functions.php';

// Определение текущего URI
$request = trim($_SERVER['REQUEST_URI'], '/');


if (preg_match('#^product/([^/]+)$#', $request, $matches)) {
    // Получаем slug товара из URL
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/pages/product.php';
}

// Обработка красивых URL
if (preg_match('#^category/([^/]+)$#', $request, $matches)) {
    // Категории
    $_GET['slug'] = $matches[1]; // Передаем slug в GET
    require __DIR__ . '/pages/category.php';
} elseif (preg_match('#^product/([0-9]+)$#', $request, $matches)) {
    // Товары
    $_GET['id'] = $matches[1]; // Передаем ID товара в GET
    require __DIR__ . '/pages/product.php';
} else {
    // Стандартная маршрутизация
    $page = $_GET['page'] ?? 'home';
    $routes = [
        'home' => 'home.php',
        'profile' => 'profile.php',
        'cart' => 'cart.php',
        'wishlist' => 'wishlist.php',
        'orders' => 'orders.php',
        'login' => 'login.php',
        'register' => 'register.php',
    ];

    if (array_key_exists($page, $routes)) {
        require __DIR__ . '/pages/' . $routes[$page];
    } else {
        error_log("Попытка доступа к несуществующей странице: {$page}");
        require __DIR__ . '/pages/error.php';
    }
}