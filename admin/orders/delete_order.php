<?php
session_start();
require '../../includes/db.php';

$order_id = $_GET['id'] ?? null;

if (!$order_id || !is_numeric($order_id)) {
    header('Location: orders_list.php');
    exit;
}

// Проверяем, существует ли заказ
$stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
if (!$stmt->fetch()) {
    header('Location: orders_list.php');
    exit;
}

// Удаляем
try {
    $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);
    $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$order_id]);

    header('Location: orders_list.php?status=deleted');
    exit;
} catch (PDOException $e) {
    die("Ошибка при удалении: " . $e->getMessage());
}