<?php
// admin/dashboard.php

session_start();
require '../includes/functions.php'; // Подключение функций

if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php'); // Перенаправляем неавторизованных пользователей
}

require '../includes/db.php'; // Подключение к базе данных

// Получение статистики
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Панель управления</h1>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Пользователи</h5>
                        <p class="card-text"><?= $total_users ?></p>
                        <a href="/admin/users/users_list.php" class="btn btn-primary">Управление</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Товары</h5>
                        <p class="card-text"><?= $total_products ?></p>
                        <a href="/admin/products/products_list.php" class="btn btn-primary">Управление</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Заказы</h5>
                        <p class="card-text"><?= $total_orders ?></p>
                        <a href="/admin/orders/orders_list.php" class="btn btn-primary">Управление</a>
                    </div>
                </div>
            </div>
        </div>

        <a href="/admin/logout.php" class="btn btn-danger mt-3">Выйти</a>
    </div>
</body>
</html>