<?php
// includes/functions.php

// Генерация CSRF-токена
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Перенаправление на другую страницу
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

// Проверка, авторизован ли пользователь
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Выход из системы
if (!function_exists('logout')) {
    function logout() {
        session_start();
        if (!isLoggedIn()) {
            redirect('/pages/login.php');
        }
        $_SESSION = [];
        session_destroy();

        // Сообщение об успешном выходе
        session_start();
        $_SESSION['message'] = "Вы успешно вышли из системы.";
        redirect('/index.php');
    }
}
?>