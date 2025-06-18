<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header('Location: /pages/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем данные пользователя из таблицы users
$stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Если пользователь не найден (например, удалён из базы), перенаправляем на вход
    header('Location: /pages/login.php');
    exit;
}

// Получаем название магазина продавца
$stmt = $pdo->prepare("SELECT store_name FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$store_name = $stmt->fetchColumn();

// Получаем количество товаров продавца
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = (SELECT id FROM sellers WHERE user_id = ?)");
$stmt->execute([$user_id]);
$products_count = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Профиль пользователя</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>

<main class="container mt-5">
    <h1>Профиль пользователя</h1>

    <div class="card p-4 mb-4 shadow-sm">
        <h3>Личные данные</h3>
        <p><strong>Имя пользователя:</strong> <?= htmlspecialchars($user['username']) ?></p>
        <p><strong>Название магазина:</strong> <?= htmlspecialchars($store_name) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Роль:</strong> <?= htmlspecialchars(ucfirst($user['role'])) ?></p>
    </div>

    <div class="card p-4 shadow-sm">
        <h3>Статистика продавца</h3>
        <p>Количество ваших товаров: <strong><?= $products_count ?></strong></p>
        <a href="/seller/products/products_list.php" class="btn btn-primary">Управлять товарами</a>
    </div>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
