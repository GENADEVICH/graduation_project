<?php
// admin/products/delete_product.php

session_start();
require '../../includes/functions.php';
require '../../includes/db.php';

// Проверка авторизации
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

// Получение ID товара из URL
$product_id = $_GET['id'] ?? null;

if ($product_id) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
}

redirect('/admin/products/products_list.php');
?>