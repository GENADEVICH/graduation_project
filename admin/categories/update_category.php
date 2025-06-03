<?php
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}

$id = $_POST['id'] ?? null;
$name = trim($_POST['name'] ?? '');
$parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
$slug = trim($_POST['slug'] ?? '');

if (!$id || empty($name)) {
    header('Location: /admin/categories/categories_list.php');
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE categories SET name = ?, parent_id = ?, slug = ? WHERE id = ?");
    $stmt->execute([$name, $parent_id, $slug ?: null, $id]);

    header('Location: /admin/categories/categories_list.php?status=updated');
    exit;
} catch (PDOException $e) {
    die("Ошибка при обновлении категории: " . $e->getMessage());
}