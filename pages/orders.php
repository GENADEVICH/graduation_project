<?php

session_start();
require '../includes/db.php';
require '../includes/functions.php';

// Проверка авторизации
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$errors = [];
$success = '';

// Получение информации о пользователе
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, profile_picture, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Проверка наличия пользователя
if (!$user) {
    $errors['general'] = "Пользователь не найден.";
}

// Получение списка заказов пользователя
$stmt_orders = $pdo->prepare("SELECT * FROM orders WHERE buyer_id = ? ORDER BY order_date DESC");
$stmt_orders->execute([$user_id]);
$orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

// Разделение заказов на актуальные, завершённые и отменённые
$active_orders = [];
$completed_orders = [];
$canceled_orders = [];

// Функция для перевода статусов
function translateStatus($status) {
    switch ($status) {
        case 'pending':
            return 'Ожидает обработки';
        case 'shipped':
            return 'Отправлен';
        case 'paid':
            return 'Оплачен';
        case 'delivered':
            return 'Доставлен';
        case 'completed':
            return 'Завершён';
        case 'cancelled':
            return 'Отменён';
        default:
            return $status;
    }
}

foreach ($orders as $order) {
    // Актуальные заказы (pending и shipped)
    if ($order['status'] == 'pending' || $order['status'] == 'shipped' || $order['status'] == 'paid') {
        $active_orders[] = $order;
    }
    // Завершённые заказы (delivered и completed)
    elseif ($order['status'] == 'delivered' || $order['status'] == 'completed') {
        $completed_orders[] = $order;
    }
    // Отменённые заказы
    elseif ($order['status'] == 'cancelled') {
        $canceled_orders[] = $order;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои Заказы</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container mt-4">
    <div class="row justify-content-center">
        <!-- Левый блок с информацией о пользователе -->
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-body text-center">
                    <h1 class="card-title mb-4">Мои Заказы</h1>

                    <!-- Вкладки -->
                    <ul class="nav nav-tabs" id="orderTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="active-tab" data-bs-toggle="tab" href="#active-orders" role="tab" aria-controls="active-orders" aria-selected="true">Актуальные</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="completed-tab" data-bs-toggle="tab" href="#completed-orders" role="tab" aria-controls="completed-orders" aria-selected="false">Завершённые</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="canceled-tab" data-bs-toggle="tab" href="#canceled-orders" role="tab" aria-controls="canceled-orders" aria-selected="false">Отменённые</a>
                        </li>
                    </ul>
                    <div class="tab-content mt-3" id="orderTabsContent">
                        <!-- Актуальные заказы -->
                        <div class="tab-pane fade show active" id="active-orders" role="tabpanel" aria-labelledby="active-tab">
                            <?php if (empty($active_orders)): ?>
                                <div class="alert alert-info">У вас нет актуальных заказов.</div>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($active_orders as $order): ?>
                                        <li class="list-group-item">
                                            <a href="/pages/order_details.php?id=<?= $order['id'] ?>">
                                                Заказ #<?= htmlspecialchars($order['order_number']) ?> - <?= translateStatus($order['status']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <!-- Завершённые заказы -->
                        <div class="tab-pane fade" id="completed-orders" role="tabpanel" aria-labelledby="completed-tab">
                            <?php if (empty($completed_orders)): ?>
                                <div class="alert alert-info">У вас нет завершённых заказов.</div>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($completed_orders as $order): ?>
                                        <li class="list-group-item">
                                            <a href="/pages/order_details.php?id=<?= $order['id'] ?>">
                                                Заказ #<?= htmlspecialchars($order['order_number']) ?> - <?= translateStatus($order['status']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <!-- Отменённые заказы -->
                        <div class="tab-pane fade" id="canceled-orders" role="tabpanel" aria-labelledby="canceled-tab">
                            <?php if (empty($canceled_orders)): ?>
                                <div class="alert alert-info">У вас нет отменённых заказов.</div>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($canceled_orders as $order): ?>
                                        <li class="list-group-item">
                                            <a href="/pages/order_details.php?id=<?= $order['id'] ?>">
                                                Заказ #<?= htmlspecialchars($order['order_number']) ?> - <?= translateStatus($order['status']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
