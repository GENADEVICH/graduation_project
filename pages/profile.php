<?php
// pages/profile.php

session_start();
require '../includes/db.php'; // Подключение к базе данных
require '../includes/functions.php'; // Подключение функций

// Проверка, авторизован ли пользователь
if (!isLoggedIn()) {
    redirect('/pages/login.php'); // Перенаправляем неавторизованных пользователей
}

$errors = []; // Массив для хранения ошибок
$success = ''; // Сообщение об успешном обновлении

// Получаем данные текущего пользователя
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $errors['general'] = "Пользователь не найден.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
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

    // Валидация пароля (если пользователь решил его изменить)
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors['password'] = "Пароль должен быть не менее 8 символов.";
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'] = "Пароль должен содержать хотя бы одну букву и одну цифру.";
        }

        if ($password !== $confirm_password) {
            $errors['confirm_password'] = "Пароли не совпадают.";
        }
    }

    // Проверка уникальности email и имени пользователя
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
        $stmt->execute([$email, $username, $user_id]);
        if ($stmt->fetch()) {
            $errors['email'] = "Пользователь с таким email или именем уже существует.";
        }
    }

    // Обновление данных пользователя
    if (empty($errors)) {
        if (!empty($password)) {
            // Если пароль изменен, хэшируем его
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$username, $email, $hashed_password, $user_id]);
        } else {
            // Если пароль не изменен, обновляем только имя и email
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $user_id]);
        }

        // Обновляем данные в сессии
        $_SESSION['username'] = $username;
        $success = "Данные успешно обновлены.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container">
        <h1>Профиль</h1>
        <?php if (!empty($errors['general'])): ?>
            <p class="error"><?= htmlspecialchars($errors['general']) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="username">Имя пользователя:</label>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
            <?php if (!empty($errors['username'])): ?>
                <p class="error"><?= htmlspecialchars($errors['username']) ?></p>
            <?php endif; ?>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            <?php if (!empty($errors['email'])): ?>
                <p class="error"><?= htmlspecialchars($errors['email']) ?></p>
            <?php endif; ?>

            <label for="password">Новый пароль:</label>
            <input type="password" name="password" id="password">
            <?php if (!empty($errors['password'])): ?>
                <p class="error"><?= htmlspecialchars($errors['password']) ?></p>
            <?php endif; ?>

            <label for="confirm_password">Подтвердите новый пароль:</label>
            <input type="password" name="confirm_password" id="confirm_password">
            <?php if (!empty($errors['confirm_password'])): ?>
                <p class="error"><?= htmlspecialchars($errors['confirm_password']) ?></p>
            <?php endif; ?>

            <button type="submit" class="btn">Обновить данные</button>
        </form>
        <p><a href="/pages/logout.php">Выйти</a></p>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>