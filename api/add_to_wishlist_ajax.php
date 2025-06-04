<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Вы должны войти в аккаунт']);
    exit;
}

$product_id = intval($_GET['id'] ?? 0);
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID товара']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем JSON тело запроса
$input = json_decode(file_get_contents('php://input'), true);
$remove = $input['remove'] ?? false;

try {
    if ($remove) {
        // Удаляем товар из избранного
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Товар удалён из избранного'
        ]);
        exit;
    } else {
        // Проверяем, есть ли товар в избранном
        $stmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);

        if ($stmt->fetchColumn()) {
            echo json_encode(['success' => false, 'message' => 'Товар уже в избранном']);
            exit;
        }

        // Добавляем товар в избранное
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $product_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Товар добавлен в избранное'
        ]);
        exit;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
}
