<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
$stmt = $pdo->prepare("SELECT username, email, profile_picture FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $errors['general'] = "Пользователь не найден.";
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль</title>

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
    <div style="height: 1220px">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h1 class="card-title mb-4">Профиль</h1>

                        <!-- Вывод фото пользователя -->
                        <div class="profile-photo mb-4">
                            <?php if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                                <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Фото пользователя" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <img src="/assets/images/default_profile.png" alt="Заглушка фото пользователя" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php endif; ?>
                        </div>

                        <!-- Вывод данных пользователя -->
                        <p class="mb-2"><strong>Имя пользователя:</strong> <?= htmlspecialchars($user['username']) ?></p>
                        <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>

                        <!-- Кнопки -->
                        <a href="/pages/logout.php" class="btn btn-danger me-2">
                            <i class="bi bi-box-arrow-right"></i> Выйти
                        </a>
                        <a href="/pages/edit-profile.php" class="btn btn-primary">
                            <i class="bi bi-pencil-square"></i> Редактировать профиль
                        </a>

                        <!-- Сообщения об ошибках и успехах -->
                        <?php if (!empty($errors['general'])): ?>
                            <div class="alert alert-danger mt-3"><?= htmlspecialchars($errors['general']) ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success mt-3"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </main>

    <!-- Bootstrap JS и зависимости -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>