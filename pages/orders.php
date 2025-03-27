<?php
// pages/orders.php

require_once __DIR__ . '/../includes/db.php'; // Подключаем db.php
require_once __DIR__ . '/../includes/functions.php'; // Подключаем functions.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    header("Location: /pages/login.php");
    exit;
}

// Получаем ID текущего пользователя
$buyer_id = $_SESSION['user']['id'];

// Проверяем, является ли пользователь администратором
$is_admin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === 1;

// Загрузка заказов
try {
    if ($is_admin) {
        // Администратор видит все заказы
        $stmt = $pdo->query("SELECT * FROM orders ORDER BY order_date DESC");
    } else {
        // Обычный пользователь видит только свои заказы
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE buyer_id = ? ORDER BY order_date DESC");
        $stmt->execute([$buyer_id]);
    }
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при выполнении запроса: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои заказы</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Собственные стили -->
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mt-4">
        <h2><?= $is_admin ? 'Все заказы' : 'Мои заказы' ?></h2>

        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                <?= $is_admin ? 'Нет заказов для отображения.' : 'У вас пока нет заказов.' ?>
            </div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID заказа</th>
                        <th>Дата</th>
                        <th>Статус</th>
                        <th>Сумма</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($order['id']) ?></td>
                            <td><?= htmlspecialchars($order['order_date']) ?></td>
                            <td>
                                <span class="badge 
                                    <?= $order['status'] === 'pending' ? 'bg-warning' : '' ?>
                                    <?= $order['status'] === 'completed' ? 'bg-success' : '' ?>
                                    <?= $order['status'] === 'cancelled' ? 'bg-danger' : '' ?>">
                                    <?= htmlspecialchars($order['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($order['total_price']) ?> руб.</td>
                            <td>
                                <a href="/pages/order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> Подробнее
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>