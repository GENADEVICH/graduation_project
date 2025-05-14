<?php
session_start();
require '../../includes/db.php';


$product_id = $_GET['id'] ?? null;

if (!$product_id || !is_numeric($product_id)) {
    header('Location: products_list.php');
    exit;
}

// Проверяем, существует ли товар
$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$product_id]);
if (!$stmt->fetch()) {
    header('Location: products_list.php');
    exit;
}

// Удаляем товар
try {
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$product_id]);
} catch (PDOException $e) {
    die("Ошибка при удалении: " . $e->getMessage());
}

header('Location: products_list.php?status=deleted');
exit;