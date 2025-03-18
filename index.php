<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

session_start();

// Подключение общих файлов
require 'includes/db.php';
require 'includes/functions.php';

// Роутинг
$page = $_GET['page'] ?? 'home';

$allowed_pages = ['home', 'product', 'cart', 'checkout', 'login', 'register'];
if (in_array($page, $allowed_pages)) {
    require "pages/{$page}.php";
} else {
    echo "Страница не найдена.";
}
