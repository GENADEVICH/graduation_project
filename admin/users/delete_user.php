<?php
session_start();
require '../../includes/db.php';


$user_id = $_GET['id'] ?? null;

if ($user_id && is_numeric($user_id)) {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        die("Ошибка при удалении пользователя: " . $e->getMessage());
    }
}

header('Location: users_list.php');
exit;