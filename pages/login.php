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
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'] ?? 'buyer';

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
                        <h2 class="card-title mb-4">Вход</h2>

                        <?php if (!empty($errors['general'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
                        <?php endif; ?>

                        <form method="POST" class="text-start">
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

                            <!-- Кнопка входа -->
                            <button type="submit" class="btn btn-primary w-100">Войти</button>
                        </form>
                        <p class="mt-3 mb-0">
                                <a href="/pages/forgot-password.php" class="text-decoration-none">Забыли пароль?</a>
                            </p>

                        <!-- Ссылка на регистрацию -->
                        <p class="mt-3 mb-0">
                            Нет аккаунта? <a href="/pages/register.php" class="text-decoration-none">Зарегистрируйтесь</a>
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
