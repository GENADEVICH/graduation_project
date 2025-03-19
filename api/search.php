<?php
// api/search.php

require_once __DIR__ . '/../includes/db.php'; // Подключаем db.php

header('Content-Type: application/json');

$query = trim($_GET['query'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

try {
    // Ищем товары, содержащие введённые символы
    $stmt = $pdo->prepare("SELECT id, name FROM products WHERE name LIKE :query LIMIT 10");
    $stmt->execute(['query' => "%$query%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка при выполнении запроса']);
}