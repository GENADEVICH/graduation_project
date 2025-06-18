<?php
session_start();
require '../../includes/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header('Location: /pages/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller_id = $stmt->fetchColumn();

$order_id = $_GET['id'] ?? null;

if (!$order_id) {
    die("Некорректный ID заказа.");
}

// Массив русских названий статусов
$status_labels = [
    'pending' => 'В ожидании',
    'processing' => 'В обработке',
    'shipped' => 'Отправлен',
    'delivered' => 'Доставлен',
    'cancelled' => 'Отменён',
];

try {
    // Проверяем, что заказ принадлежит продавцу через товары
    $stmt = $pdo->prepare("
        SELECT o.*, u.first_name, u.last_name, u.email
        FROM orders o
        JOIN users u ON o.buyer_id = u.id
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        WHERE o.id = ? AND p.seller_id = ?
        LIMIT 1
    ");
    $stmt->execute([$order_id, $seller_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("Заказ не найден или у вас нет доступа.");
    }

    // Получаем товары из заказа
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.price 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Ошибка при загрузке заказа: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Просмотр заказа #<?= htmlspecialchars($order['order_number']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"  rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"  rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="/seller/dashboard.php"><i class="bi bi-shop-window me-2"></i>Панель продавца</a>
        <a href="/pages/profile.php" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Выйти</a>
    </div>
</nav>

<div class="container">
    <h1 class="mb-4">Заказ #<?= htmlspecialchars($order['order_number']) ?></h1>

    <h4>Покупатель</h4>
    <p>
        <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?><br>
        Email: <?= htmlspecialchars($order['email']) ?>
    </p>

    <h4>Данные заказа</h4>
    <p><strong>Дата:</strong> <?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></p>
    <p><strong>Статус:</strong> <?= htmlspecialchars($status_labels[$order['status']] ?? $order['status']) ?></p>

    <h4>Товары в заказе</h4>
    <?php if (empty($items)): ?>
        <p>Товары не найдены.</p>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Количество</th>
                    <th>Цена за шт.</th>
                    <th>Сумма</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = 0;
                foreach ($items as $item): 
                    $sum = $item['quantity'] * $item['price'];
                    $total += $sum;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= (int)$item['quantity'] ?></td>
                        <td><?= number_format($item['price'], 2, ',', ' ') ?> ₽</td>
                        <td><?= number_format($sum, 2, ',', ' ') ?> ₽</td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" class="text-end"><strong>Итого:</strong></td>
                    <td><strong><?= number_format($total, 2, ',', ' ') ?> ₽</strong></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="/seller/orders/orders_list.php" class="btn btn-secondary mt-3">Назад к заказам</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
