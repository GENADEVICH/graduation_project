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

/**
 * Функция для добавления товара в корзину
 */
if (!function_exists('addToCart')) {
    function addToCart($pdo, $product_id) {
        if (!isLoggedIn()) {
            return ['error' => 'Необходимо авторизоваться'];
        }

        if (!$product_id) {
            return ['error' => 'Неверный ID товара'];
        }

        $user_id = $_SESSION['user_id'];

        try {
            // Проверяем, существует ли товар в корзине
            $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingItem) {
                // Если товар уже есть, увеличиваем количество
                $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$user_id, $product_id]);
            } else {
                // Если товара нет, добавляем его
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
                $stmt->execute([$user_id, $product_id]);
            }

            return ['success' => true];
        } catch (PDOException $e) {
            return ['error' => 'Ошибка базы данных: ' . $e->getMessage()];
        }
    }
}

/**
 * Функция для добавления товара в избранное
 */
if (!function_exists('addToWishlist')) {
    function addToWishlist($pdo, $product_id) {
        if (!isLoggedIn()) {
            return ['error' => 'Необходимо авторизоваться'];
        }

        if (!$product_id) {
            return ['error' => 'Неверный ID товара'];
        }

        $user_id = $_SESSION['user_id'];

        try {
            // Проверяем, существует ли товар в избранном
            $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingItem) {
                return ['error' => 'Товар уже в избранном'];
            } else {
                // Добавляем товар в избранное
                $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $product_id]);
                return ['success' => true];
            }
        } catch (PDOException $e) {
            return ['error' => 'Ошибка базы данных: ' . $e->getMessage()];
        }
    }
}