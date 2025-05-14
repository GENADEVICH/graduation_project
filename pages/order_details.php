<?php
// pages/order_details.php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    header("Location: /pages/login.php");
    exit;
}

$order_id = $_GET['id'] ?? null;

if (!$order_id) {
    header("Location: /pages/orders.php");
    exit;
}

try {
    // Получаем данные заказа
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // Проверяем права доступа
    if (!$order || (!$is_admin && $order['user_id'] != $_SESSION['user']['id'])) {
        header("Location: /pages/orders.php");
        exit;
    }

    // Получаем товары в заказе
    $stmt = $pdo->prepare("SELECT oi.*, p.name, p.main_image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при выполнении запроса: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Детали заказа #<?= htmlspecialchars($order['id']) ?></title>

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
        <h2>Детали заказа #<?= htmlspecialchars($order['id']) ?></h2>

        <div class="mb-4">
            <p><strong>Дата:</strong> <?= htmlspecialchars($order['order_date']) ?></p>
            <p><strong>Статус:</strong>
                <span class="badge 
                    <?= $order['status'] === 'pending' ? 'bg-warning' : '' ?>
                    <?= $order['status'] === 'completed' ? 'bg-success' : '' ?>
                    <?= $order['status'] === 'cancelled' ? 'bg-danger' : '' ?>">
                    <?= htmlspecialchars($order['status']) ?>
                </span>
            </p>
            <p><strong>Общая сумма:</strong> <?= htmlspecialchars($order['total_price']) ?> руб.</p>
        </div>

        <h4>Товары в заказе</h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Изображение</th>
                    <th>Наименование</th>
                    <th>Количество</th>
                    <th>Цена</th>
                    <th>Сумма</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="rounded" style="width: 50px;">
                            <?php else: ?>
                                <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="rounded" style="width: 50px;">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                        <td><?= htmlspecialchars($item['price']) ?> руб.</td>
                        <td><?= htmlspecialchars($item['quantity'] * $item['price']) ?> руб.</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>