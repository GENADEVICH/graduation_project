<?php
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header('Location: /pages/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller_id = $stmt->fetchColumn();


// Массив русских названий статусов
$status_labels = [
    'pending' => 'В ожидании',
    'processing' => 'В обработке',
    'shipped' => 'Отправлен',
    'delivered' => 'Доставлен',
    'cancelled' => 'Отменён',
];

try {
    // Получаем заказы, где есть товары этого продавца
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            o.id,
            o.order_number,
            o.buyer_id,
            o.order_date,
            o.status,
            o.total_price,
            u.username AS buyer_name
        FROM orders o
        JOIN users u ON o.buyer_id = u.id
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        WHERE p.seller_id = ?
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$seller_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при получении заказов: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Заказы продавца</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"  rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="/seller/dashboard.php"><i class="bi bi-shop-window me-2"></i>Панель продавца</a>
        <a href="/pages/logout.php" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Выйти</a>
    </div>
</nav>

<div class="container">
    <h1 class="mb-4">Заказы</h1>

    <?php if (empty($orders)): ?>
        <p>У вас пока нет заказов.</p>
    <?php else: ?>
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Номер заказа</th>
                    <th>Покупатель</th>
                    <th>Дата заказа</th>
                    <th>Статус</th>
                    <th>Сумма</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['order_number']) ?></td>
                        <td><?= htmlspecialchars($order['buyer_name']) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></td>
                        <td><?= htmlspecialchars($status_labels[$order['status']] ?? $order['status']) ?></td>
                        <td><?= number_format($order['total_price'], 2, ',', ' ') ?> ₽</td>
                        <td>
                            <a href="/seller/orders/order_view.php?id=<?= $order['id'] ?>" class="btn btn-primary btn-sm">Просмотр</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
