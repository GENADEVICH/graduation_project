<?php
// admin/users/delete_user.php

session_start();
require '../../includes/functions.php';
require '../../includes/db.php';

// Проверка авторизации
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

// Получение ID пользователя из URL
$user_id = $_GET['id'] ?? null;

if ($user_id) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
}

redirect('/admin/users/users_list.php');
?>