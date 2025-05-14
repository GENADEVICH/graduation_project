<?php
session_start();
require '../../includes/db.php';

$order_id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$order_id || !$status || !in_array($status, ['pending', 'paid', 'shipped', 'delivered', 'cancelled'])) {
    header('Location: orders_list.php');
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);

    header('Location: orders_list.php?status=updated');
    exit;
} catch (PDOException $e) {
    die("Ошибка при обновлении: " . $e->getMessage());
}