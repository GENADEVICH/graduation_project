<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

$token = $_GET['token'] ?? null;
$email = null;
$errors = [];
$success = false;

if (!$token) {
    die("Токен отсутствует.");
}

// Проверяем токен
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    die("Неверный или истёкший токен.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($password)) {
        $errors[] = "Пароль обязателен.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Пароль должен быть не менее 6 символов.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Пароли не совпадают.";
    }

    if (empty($errors)) {
        $email = $reset['email'];
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hash, $email]);

        // Удаляем токен
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сбросить пароль</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="mb-4 text-center">Сбросить пароль</h2>

            <?php if ($success): ?>
                <div class="alert alert-success">Пароль успешно изменён. <a href="/pages/login.php">Войти</a></div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><?= implode('<br>', $errors) ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="password" class="form-label">Новый пароль</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Подтвердите пароль</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Изменить пароль</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>