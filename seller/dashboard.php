<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header('Location: /pages/login.php');
    exit; 
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller_id = $stmt->fetchColumn();


try {
    // Количество товаров продавца
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
    $stmt->execute([$seller_id]);
    $product_count = $stmt->fetchColumn();

    // Количество заказов, где есть товары этого продавца (уникальные заказы)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id)
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p ON p.id = oi.product_id
        WHERE p.seller_id = ?
    ");
    $stmt->execute([$seller_id]);
    $order_count = $stmt->fetchColumn();

    // Общая выручка по товарам продавца
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(oi.quantity * oi.price), 0) 
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        WHERE p.seller_id = ?
    ");
    $stmt->execute([$seller_id]);
    $total_revenue = $stmt->fetchColumn();

    // Последние 5 заказов с товарами продавца
    $stmt = $pdo->prepare("
        SELECT o.id, o.order_number, o.order_date, o.status, SUM(oi.quantity * oi.price) as order_total
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p ON p.id = oi.product_id
        WHERE p.seller_id = ?
        GROUP BY o.id, o.order_date, o.status
        ORDER BY o.order_date DESC
        LIMIT 5
    ");
    $stmt->execute([$seller_id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Ошибка при получении данных: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель продавца</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }
        .card-icon {
            font-size: 2rem;
            opacity: 0.2;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            transition: all 0.2s ease-in-out;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="#"><i class="bi bi-shop-window me-2"></i>Панель продавца</a>
        <a href="/pages/profile.php" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Выйти</a>
    </div>
</nav>

<div class="container">
    <div class="mb-4">
        <h1>Добро пожаловать, <?= htmlspecialchars($_SESSION['username'] ?? 'Гость') ?>!</h1>
        <p class="text-muted">Вы вошли как продавец.</p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title text-muted">Мои товары</h5>
                        <h2 class="display-6"><?= $product_count ?></h2>
                    </div>
                    <i class="bi bi-box-seam card-icon text-warning"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title text-muted">Мои заказы</h5>
                        <h2 class="display-6"><?= $order_count ?></h2>
                    </div>
                    <i class="bi bi-bag-check card-icon text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title text-muted">Общая выручка</h5>
                        <h2 class="display-6"><?= number_format($total_revenue, 2, ',', ' ') ?> ₽</h2>
                    </div>
                    <i class="bi bi-currency-dollar card-icon text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <h4>Последние заказы</h4>

        <?php if (empty($recent_orders)): ?>
            <p class="text-muted">Заказы отсутствуют.</p>
        <?php else: ?>
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Номер заказа</th>
                    <th>Дата</th>
                    <th>Статус</th>
                    <th>Сумма</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['order_number']) ?></td>
                        <td><?= htmlspecialchars(date('d.m.Y H:i', strtotime($order['order_date']))) ?></td>
                        <td>
                            <?php
                            switch ($order['status']) {
                                case 'pending':
                                    echo '<span class="badge bg-warning text-dark">В ожидании</span>';
                                    break;
                                case 'processing':
                                    echo '<span class="badge bg-info text-dark">В обработке</span>';
                                    break;
                                case 'completed':
                                    echo '<span class="badge bg-success">Завершён</span>';
                                    break;
                                case 'cancelled':
                                    echo '<span class="badge bg-danger">Отменён</span>';
                                    break;
                                case 'delivered':
                                    echo '<span class="badge bg-primary">Доставлен</span>';
                                    break;
                                default:
                                    echo '<span class="badge bg-secondary">Неизвестно</span>';
                            }
                            ?>
                        </td>
                        <td><?= number_format($order['order_total'], 2, ',', ' ') ?> ₽</td>
                        <td>
                            <a href="/seller/orders/order_view.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">Просмотр</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <h4>Быстрое меню</h4>
        <div class="row g-3">
            <div class="col-md-4">
                <a href="/seller/products/products_list.php" class="btn btn-outline-warning w-100 p-3">
                    <i class="bi bi-box-seam me-2"></i>Мои товары
                </a>
            </div>
            <div class="col-md-4">
                <a href="/seller/orders/orders_list.php" class="btn btn-outline-success w-100 p-3">
                    <i class="bi bi-bag-check me-2"></i>Мои заказы
                </a>
            </div>
            <div class="col-md-4">
                <a href="/seller/profile.php" class="btn btn-outline-info w-100 p-3">
                    <i class="bi bi-person-circle me-2"></i>Профиль
                </a>
            </div>
        </div>
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
