<?php
// pages/login.php

session_start();
require '../includes/db.php';
require '../includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Валидация
    if (empty($email)) {
        $errors['email'] = "Email обязателен.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Некорректный формат email.";
    }

    if (empty($password)) {
        $errors['password'] = "Пароль обязателен.";
    }

    // Авторизация
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            redirect('/pages/profile.php');
        } else {
            $errors['general'] = "Неверный email или пароль.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container">
        <h1>Вход</h1>
        <?php if (!empty($errors['general'])): ?>
            <p class="error"><?= htmlspecialchars($errors['general']) ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
            <?php if (!empty($errors['email'])): ?>
                <p class="error"><?= htmlspecialchars($errors['email']) ?></p>
            <?php endif; ?>

            <label for="password">Пароль:</label>
            <input type="password" name="password" id="password" required>
            <?php if (!empty($errors['password'])): ?>
                <p class="error"><?= htmlspecialchars($errors['password']) ?></p>
            <?php endif; ?>

            <button type="submit" class="btn">Войти</button>
        </form>
        <p>Нет аккаунта? <a href="/pages/register.php">Зарегистрируйтесь</a></p>
    </main>
