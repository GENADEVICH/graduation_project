<?php
// pages/register.php

session_start();
require '../includes/db.php'; // Подключение к базе данных
require '../includes/functions.php'; // Подключение функций

$errors = []; // Массив для хранения ошибок
$username = ''; // По умолчанию поле "Имя пользователя" пустое

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение данных из формы
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Валидация имени пользователя
    if (empty($username)) {
        $errors['username'] = "Имя пользователя обязательно.";
    } elseif (!preg_match('/^[a-zA-Z0-9]{3,}$/', $username)) {
        $errors['username'] = "Имя пользователя должно содержать только буквы и цифры и быть не менее 3 символов.";
    }

    // Валидация email
    if (empty($email)) {
        $errors['email'] = "Email обязателен.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Некорректный формат email.";
    }

    // Валидация пароля
    if (empty($password)) {
        $errors['password'] = "Пароль обязателен.";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Пароль должен быть не менее 8 символов.";
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = "Пароль должен содержать хотя бы одну букву и одну цифру.";
    }

    // Проверка совпадения паролей
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Пароли не совпадают.";
    }

    // Проверка уникальности email и имени пользователя
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $errors['email'] = "Пользователь с таким email или именем уже существует.";
        }
    }

    // Регистрация пользователя
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashed_password])) {
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            redirect('/pages/profile.php'); // Перенаправление на страницу профиля
        } else {
            $errors['general'] = "Ошибка при регистрации.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container">
        <h1>Регистрация</h1>
        <?php if (!empty($errors['general'])): ?>
            <p class="error"><?= htmlspecialchars($errors['general']) ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="username">Имя пользователя:</label>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>" required>
            <?php if (!empty($errors['username'])): ?>
                <p class="error"><?= htmlspecialchars($errors['username']) ?></p>
            <?php endif; ?>

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

            <label for="confirm_password">Подтвердите пароль:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
            <?php if (!empty($errors['confirm_password'])): ?>
                <p class="error"><?= htmlspecialchars($errors['confirm_password']) ?></p>
            <?php endif; ?>

            <button type="submit" class="btn">Зарегистрироваться</button>
        </form>
        <p>Уже есть аккаунт? <a href="/pages/login.php">Войдите</a></p>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>