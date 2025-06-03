<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? null;
$quantity = $_POST['quantity'] ?? 1;

if (!$product_id || $quantity < 1) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Проверим, есть ли этот товар уже в корзине
$stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    // Если есть, увеличим количество
    $newQuantity = $existing['quantity'] + $quantity;
    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$newQuantity, $user_id, $product_id]);
} else {
    // Если нет, добавим новую запись
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $product_id, $quantity]);
}

echo json_encode(['success' => true]);
