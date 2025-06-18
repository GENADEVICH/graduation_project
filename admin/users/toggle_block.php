<?php
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}

$user_id = $_GET['id'] ?? null;

if (!$user_id || !is_numeric($user_id)) {
    die("Некорректный ID пользователя.");
}

try {
    // Получаем текущее состояние блокировки
    $stmt = $pdo->prepare("SELECT is_blocked FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Пользователь не найден.");
    }

    $new_status = $user['is_blocked'] ? 0 : 1;

    // Обновляем статус
    $stmt = $pdo->prepare("UPDATE users SET is_blocked = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);

    // Перенаправление обратно к списку
    header('Location: /admin/users/users_list.php');
    exit;

} catch (PDOException $e) {
    die("Ошибка при изменении статуса: " . $e->getMessage());
}
?>