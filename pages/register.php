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
    } elseif (!preg_match('/^[a-zA-Zа-яА-Я0-9]{3,}$/', $username)) {
        $errors['username'] = "Имя пользователя должно содержать только буквы (английские или русские) и цифры, и быть не менее 3 символов.";
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

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons (опционально) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Твои собственные стили -->
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h2 class="card-title mb-4">Регистрация</h2>

                        <?php if (!empty($errors['general'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
                        <?php endif; ?>

                        <form method="POST" class="text-start">
                            <!-- Имя пользователя -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Имя пользователя:</label>
                                <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($username) ?>" placeholder="Введите имя пользователя" required>
                                <?php if (!empty($errors['username'])): ?>
                                    <div class="text-danger small"><?= htmlspecialchars($errors['username']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email:</label>
                                <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($email ?? '') ?>" placeholder="Введите ваш email" required>
                                <?php if (!empty($errors['email'])): ?>
                                    <div class="text-danger small"><?= htmlspecialchars($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Пароль -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль:</label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Введите ваш пароль" required>
                                <?php if (!empty($errors['password'])): ?>
                                    <div class="text-danger small"><?= htmlspecialchars($errors['password']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Подтверждение пароля -->
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Подтвердите пароль:</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Подтвердите ваш пароль" required>
                                <?php if (!empty($errors['confirm_password'])): ?>
                                    <div class="text-danger small"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Кнопка регистрации -->
                            <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
                        </form>

                        <!-- Ссылка на вход -->
                        <p class="mt-3 mb-0">
                            Уже есть аккаунт? <a href="/pages/login.php" class="text-decoration-none">Войдите</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS и зависимости -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>