<?php
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}

$errors = [];
$success = '';

// Получаем ID пользователя из URL
$user_id = $_GET['id'] ?? null;

if (!$user_id || !is_numeric($user_id)) {
    header('Location: users_list.php');
    exit;
}

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users_list.php');
    exit;
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');

    // Валидация
    if (empty($username)) {
        $errors[] = 'Имя пользователя не может быть пустым.';
    }
    if (empty($email)) {
        $errors[] = 'Email не может быть пустым.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный формат email.';
    }

    if (empty($role) || !in_array($role, ['buyer', 'seller', 'admin'])) {
        $errors[] = 'Некорректная роль.';
    }

    // Проверяем уникальность email
    $stmt_check_email = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt_check_email->execute([$email, $user_id]);
    if ($stmt_check_email->fetch()) {
        $errors[] = 'Этот email уже используется другим пользователем.';
    }

    // Если нет ошибок — обновляем
    if (empty($errors)) {
        try {
            $stmt_update = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
            $stmt_update->execute([$username, $email, $role, $user_id]);
            $success = 'Данные пользователя успешно обновлены.';
            // Обновляем данные после сохранения
            $user['username'] = $username;
            $user['email'] = $email;
            $user['role'] = $role;
        } catch (PDOException $e) {
            $errors[] = 'Ошибка при сохранении данных: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать пользователя</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="/admin/dashboard.php">
            <i class="bi bi-person-gear me-2"></i>Редактирование пользователя
        </a>
        <a href="/admin/users/users_list.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Назад к списку
        </a>
    </div>
</nav>

<div class="container">
    <h2 class="mb-4">Редактирование пользователя #<?= htmlspecialchars($user['id']) ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="needs-validation" novalidate>
        <!-- Имя пользователя -->
        <div class="mb-3">
            <label for="username" class="form-label">Имя пользователя</label>
            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            <div class="invalid-feedback">Введите имя пользователя.</div>
        </div>

        <!-- Email -->
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            <div class="invalid-feedback">Введите корректный email.</div>
        </div>

        <!-- Роль -->
        <div class="mb-3">
            <label for="role" class="form-label">Роль</label>
            <select class="form-select" id="role" name="role" required>
                <option value="">Выберите роль</option>
                <option value="buyer" <?= $user['role'] === 'buyer' ? 'selected' : '' ?>>Покупатель</option>
                <option value="seller" <?= $user['role'] === 'seller' ? 'selected' : '' ?>>Продавец</option>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
            </select>
            <div class="invalid-feedback">Выберите роль.</div>
        </div>

        <!-- Кнопки -->
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Сохранить изменения</button>
            <a href="/admin/users/users_list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Назад
            </a>
        </div>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap @5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Валидация формы -->
<script>
    (() => {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

</body>
</html>