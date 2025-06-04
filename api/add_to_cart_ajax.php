<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Пожалуйста, войдите в аккаунт']);
    exit;
}

$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID товара']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Проверяем, есть ли уже такой товар в корзине
$stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    $new_quantity = $existing['quantity'] + 1;
    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$new_quantity, $user_id, $product_id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
    $stmt->execute([$user_id, $product_id]);
}

echo json_encode([
    'success' => true,
    'message' => 'Товар успешно добавлен в корзину',
]);