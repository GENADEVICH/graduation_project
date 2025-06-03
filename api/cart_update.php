<?php
session_start();
header('Content-Type: application/json');

require '../includes/db.php';
require '../includes/functions.php';

// Проверка авторизации
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Проверка наличия данных
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['product_id'], $_POST['quantity'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$product_id = (int) $_POST['product_id'];
$quantity = (int) $_POST['quantity'];

if ($quantity < 1) {
    echo json_encode(['error' => 'Invalid quantity']);
    exit;
}

try {
    // Обновить количество товара в корзине
    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$quantity, $user_id, $product_id]);

    // Получить цену товара
    $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    $itemTotal = $product['price'] * $quantity;

    // Пересчитать общую сумму корзины
    $stmt = $pdo->prepare("
        SELECT SUM(p.price * c.quantity) AS total
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $result['total'] ?? 0;

    echo json_encode([
        'itemTotal' => $itemTotal,
        'total' => $total
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
