<?php
session_start();
require '../../includes/db.php';

$category_id = $_GET['id'] ?? null;

if (!$category_id || !is_numeric($category_id)) {
    header('Location: /admin/categories/categories_list.php');
    exit;
}

// Проверяем, существует ли категория
$stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
if (!$stmt->fetch()) {
    header('Location: /admin/categories/categories_list.php');
    exit;
}

// Удаляем категорию
try {
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$category_id]);
    header('Location: /admin/categories/categories_list.php?status=deleted');
    exit;
} catch (PDOException $e) {
    die("Ошибка при удалении: " . $e->getMessage());
}