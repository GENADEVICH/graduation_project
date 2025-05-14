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
    if (!$order || $order['user_id'] != $_SESSION['user']['id']) {
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

// Функция для перевода статуса
function getOrderStatus($status) {
    switch ($status) {
        case 'pending':
            return ['title' => 'Ожидает оплаты', 'class' => 'bg-warning'];
        case 'paid':
            return ['title' => 'Оплачен', 'class' => 'bg-info'];
        case 'shipped':
            return ['title' => 'Отправлен', 'class' => 'bg-primary'];
        case 'delivered':
            return ['title' => 'Доставлен', 'class' => 'bg-success'];
        case 'cancelled':
            return ['title' => 'Отменён', 'class' => 'bg-danger'];
        default:
            return ['title' => 'Неизвестно', 'class' => 'bg-secondary'];
    }
}

$statusInfo = getOrderStatus($order['status']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Детали заказа #<?= htmlspecialchars($order['id']) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap @5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons @1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Собственные стили -->
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mt-4">
        <h2 class="mb-4">Детали заказа #<?= htmlspecialchars($order['id']) ?></h2>

        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <p><strong>Дата:</strong> <?= htmlspecialchars($order['order_date']) ?></p>
                <p><strong>Статус:</strong>
                    <span class="badge <?= $statusInfo['class'] ?>">
                        <?= htmlspecialchars($statusInfo['title']) ?>
                    </span>
                </p>
                <p><strong>Общая сумма:</strong> <?= htmlspecialchars($order['total_price']) ?> ₽</p>
            </div>
        </div>

        <h4 class="mb-3">Товары в заказе</h4>
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Изображение</th>
                    <th>Наименование</th>
                    <th>Количество</th>
                    <th>Цена за ед.</th>
                    <th>Сумма</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php if (!empty($item['main_image'])): ?>
                                <img src="<?= htmlspecialchars($item['main_image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="rounded" style="width: 120px;">
                            <?php else: ?>
                                <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="rounded" style="width: 120px;">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                        <td><?= htmlspecialchars($item['price']) ?> ₽</td>
                        <td><?= htmlspecialchars($item['quantity'] * $item['price']) ?> ₽</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mt-4 text-end">
            <a href="<?= htmlspecialchars($_SERVER['HTTP_REFERER']) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Назад 
            </a>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap @5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>