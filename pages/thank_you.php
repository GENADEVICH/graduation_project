<?php
session_start();
require '../includes/db.php'; // Подключение к базе данных
require '../includes/functions.php'; // Подключение функций

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    redirect('/pages/home.php'); // Перенаправляем, если ID заказа отсутствует
}

// Получаем информацию о заказе
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirect('/pages/home.php'); // Перенаправляем, если заказ не найден
}

// Получаем товары из заказа
$stmt = $pdo->prepare("
    SELECT oi.quantity, oi.price, p.name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Спасибо за покупку!</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Твои собственные стили -->
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mt-4">
        <h1 class="text-center mb-4">Спасибо за покупку!</h1>
        <div class="alert alert-success text-center">
            Ваш заказ №<?= htmlspecialchars($order['id']) ?> успешно оформлен.<br>
            Мы свяжемся с вами для подтверждения деталей.
        </div>
        <div class="text-center">
            <p><strong>Информация о заказе:</strong></p>
            <p>Адрес доставки: <?= htmlspecialchars($order['shipping_address']) ?></p>
            <p>Общая сумма: <?= htmlspecialchars($order['total_price']) ?> руб.</p>
            <p>Статус: <?= htmlspecialchars($order['status']) ?></p>
        </div>
        <h3 class="text-center mt-4">Товары в заказе:</h3>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th scope="col">Наименование</th>
                    <th scope="col">Количество</th>
                    <th scope="col">Цена</th>
                    <th scope="col">Сумма</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                        <td><?= htmlspecialchars($item['price']) ?> руб.</td>
                        <td><?= htmlspecialchars($item['price'] * $item['quantity']) ?> руб.</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="text-center">
            <a href="/pages/home.php" class="btn btn-primary">Вернуться на главную</a>
        </div>
    </main>

    <!-- Bootstrap JS и зависимости -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>