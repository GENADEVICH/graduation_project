<?php
session_start();
require '../../includes/db.php';

$order_id = $_GET['id'] ?? null;

if (!$order_id || !is_numeric($order_id)) {
    header('Location: orders_list.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders_list.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать заказ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="/admin/orders/orders_list.php">
            <i class="bi bi-pencil-square me-2"></i>Редактировать заказ
        </a>
        <a href="/admin/orders/orders_list.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Назад
        </a>
    </div>
</nav>

<div class="container">
    <h2 class="mb-4">Редактировать заказ #<?= $order['id'] ?></h2>

    <form method="POST" action="/admin/orders/update_order.php">
        <input type="hidden" name="id" value="<?= $order['id'] ?>">

        <!-- Статус -->
        <div class="mb-3">
            <label for="status" class="form-label">Статус</label>
            <select class="form-select" id="status" name="status" required>
                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Ожидает оплаты</option>
                <option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>>Оплачен</option>
                <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Отправлен</option>
                <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Доставлен</option>
                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Отменён</option>
            </select>
        </div>

        <!-- Кнопки -->
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Сохранить изменения</button>
            <a href="/admin/orders/orders_list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Назад
            </a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap @5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>