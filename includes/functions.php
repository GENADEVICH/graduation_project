<?php
// includes/functions.php

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
        if (!isLoggedIn()) {
            redirect('/pages/login.php');
        }

        // Очистка сессии
        $_SESSION = [];

        // Уничтожение сессии
        session_destroy();

        // Удаление сессионной куки
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Перенаправление
        redirect('/index.php');
    }
}

// Подключение автозагрузчика
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!function_exists('send_email')) {
    function send_email($to, $subject, $message) {
        require_once __DIR__ . '/../vendor/autoload.php';

        $mail = new PHPMailer(true);

        try {
            // Настройки сервера
            $mail->isSMTP();                                      // Использовать SMTP
            $mail->Host       = 'ssl://smtp.spaceweb.ru';         // Сервер от SpaceWeb
            $mail->SMTPAuth   = true;                             // Включить аутентификацию
            $mail->Username   = 'support@akrapov1c.ru';           // Твой email
            $mail->Password   = 'HSV5DYGZCgVZC@DA';               // Пароль от почты
            $mail->SMTPSecure = 'ssl';                            // SSL-шифрование
            $mail->Port       = 465;                              // Порт для SSL

            // Кодировка
            $mail->CharSet = 'UTF-8';                             // ОБЯЗАТЕЛЬНО!

            // Получатель
            $mail->setFrom('support@akrapov1c.ru', 'MAXIM STROEV');
            $mail->addAddress($to);                               // Кому

            // Контент
            $mail->isHTML(true);                                  // HTML формат
            $mail->Subject = $subject;
            $mail->Body    = $message;

            // Отправляем
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Ошибка отправки email: " . $mail->ErrorInfo);
            return false;
        }
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
