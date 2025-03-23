<?php
// admin/users/edit_user.php

session_start();
require '../../includes/functions.php';
require '../../includes/db.php';

// Проверка авторизации
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

// Получение ID пользователя из URL
$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    redirect('/admin/users/users_list.php');
}

// Получение данных пользователя
$stmt = $pdo->prepare("SELECT id, username, email, role, is_blocked FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/admin/users/users_list.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $is_blocked = isset($_POST['is_blocked']) ? 1 : 0;

    // Валидация
    if (empty($username)) {
        $errors['username'] = "Имя пользователя обязательно.";
    }

    if (empty($email)) {
        $errors['email'] = "Email обязателен.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Некорректный формат email.";
    }

    // Обновление данных
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, is_blocked = ? WHERE id = ?");
        $stmt->execute([$username, $email, $role, $is_blocked, $user_id]);

        redirect('/admin/users/users_list.php');
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование пользователя</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Редактирование пользователя</h1>

        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Имя пользователя:</label>
                <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                <?php if (!empty($errors['username'])): ?>
                    <div class="text-danger small"><?= htmlspecialchars($errors['username']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                <?php if (!empty($errors['email'])): ?>
                    <div class="text-danger small"><?= htmlspecialchars($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Роль:</label>
                <select name="role" id="role" class="form-select">
                    <option value="buyer" <?= $user['role'] === 'buyer' ? 'selected' : '' ?>>Пользователь</option>
                    <option value="seller" <?= $user['role'] === 'seller' ? 'selected' : '' ?>>Продавец</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
                </select>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" name="is_blocked" id="is_blocked" class="form-check-input" <?= $user['is_blocked'] ? 'checked' : '' ?>>
                <label for="is_blocked" class="form-check-label">Заблокировать пользователя</label>
            </div>

            <button type="submit" class="btn btn-primary">Сохранить</button>
            <a href="/admin/users/users_list.php" class="btn btn-secondary">Отмена</a>
        </form>
    </div>
</body>
</html>