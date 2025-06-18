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
    $stmt = $pdo->prepare("SELECT oi.*, p.name, p.main_image FROM order_items oi 
                           JOIN products p ON oi.product_id = p.id 
                           WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем существующие отзывы пользователя для этих товаров
    $productIds = array_column($items, 'product_id');
    if (count($productIds) > 0) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $pdo->prepare("SELECT product_id FROM reviews 
                               WHERE user_id = ? AND product_id IN ($placeholders)");
        $stmt->execute(array_merge([$_SESSION['user']['id']], $productIds));
        $reviewed = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } else {
        $reviewed = [];
    }

} catch (PDOException $e) {
    die("Ошибка при выполнении запроса: " . $e->getMessage());
}

// Функция для перевода статуса
function getOrderStatus($status) {
    switch ($status) {
        case 'pending': return ['title' => 'Ожидает оплаты', 'class' => 'bg-warning'];
        case 'paid': return ['title' => 'Оплачен', 'class' => 'bg-info'];
        case 'shipped': return ['title' => 'Отправлен', 'class' => 'bg-primary'];
        case 'delivered': return ['title' => 'Доставлен', 'class' => 'bg-success'];
        case 'cancelled': return ['title' => 'Отменён', 'class' => 'bg-danger'];
        default: return ['title' => 'Неизвестно', 'class' => 'bg-secondary'];
    }
}

$statusInfo = getOrderStatus($order['status']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Детали заказа #<?= htmlspecialchars($order['id']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">

    <style>
        /* Мобильная адаптация таблицы товаров */
        @media (max-width: 767.98px) {
            table.order-table {
                display: none;
            }
            .mobile-product-card {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: .25rem;
                padding: 1rem;
                box-shadow: 0 0 5px rgba(0,0,0,.05);
            }
        }
        @media (min-width: 768px) {
            .mobile-product-card {
                display: none;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container mt-4">
    <h2 class="mb-4">Детали заказа #<?= htmlspecialchars($order['order_number']) ?></h2>

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

    <!-- Таблица для десктопа -->
    <table class="table table-hover align-middle order-table">
        <thead class="table-light">
        <tr>
            <th>Изображение</th>
            <th>Наименование</th>
            <th>Количество</th>
            <th>Цена за ед.</th>
            <th>Сумма</th>
            <th>Отзыв</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <img src="<?= htmlspecialchars($item['main_image'] ?: '/assets/images/no-image.jpg') ?>"
                         alt="<?= htmlspecialchars($item['name']) ?>" class="rounded" style="width: 120px;">
                </td>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= htmlspecialchars($item['quantity']) ?></td>
                <td><?= htmlspecialchars($item['price']) ?> ₽</td>
                <td><?= htmlspecialchars($item['quantity'] * $item['price']) ?> ₽</td>
                <td>
                    <?php if ($order['status'] === 'delivered'): ?>
                        <?php if (in_array($item['product_id'], $reviewed)): ?>
                            <span class="text-success">Отзыв оставлен</span>
                        <?php else: ?>
                            <form action="/pages/leave_review.php" method="POST">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['product_id']) ?>">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    Оставить отзыв
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">Доступно после доставки</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Карточки для мобильных -->
    <?php foreach ($items as $item): ?>
        <div class="mobile-product-card d-md-none">
            <div class="d-flex">
                <img src="<?= htmlspecialchars($item['main_image'] ?: '/assets/images/no-image.jpg') ?>"
                     alt="<?= htmlspecialchars($item['name']) ?>" class="rounded me-3" style="width: 100px; height: auto;">
                <div>
                    <h5><?= htmlspecialchars($item['name']) ?></h5>
                    <p>Количество: <strong><?= htmlspecialchars($item['quantity']) ?></strong></p>
                    <p>Цена за ед.: <strong><?= htmlspecialchars($item['price']) ?> ₽</strong></p>
                    <p>Сумма: <strong><?= htmlspecialchars($item['quantity'] * $item['price']) ?> ₽</strong></p>

                    <p>
                    <?php if ($order['status'] === 'delivered'): ?>
                        <?php if (in_array($item['product_id'], $reviewed)): ?>
                            <span class="text-success">Отзыв оставлен</span>
                        <?php else: ?>
                            <form action="/pages/leave_review.php" method="POST" class="mb-0">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['product_id']) ?>">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    Оставить отзыв
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">Доступно после доставки</span>
                    <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="mt-4 text-end">
        <a href="/pages/orders.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Назад
        </a>

    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
