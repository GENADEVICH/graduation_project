<?php
session_start();
require '../includes/db.php';

// Получаем статистику
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $order_count = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $product_count = $stmt->fetchColumn();

    // Последние заявки на регистрацию как продавец
    $stmt = $pdo->query("SELECT s.*, u.username FROM sellers s JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT 5");
    $latest_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при получении данных: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель управления</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
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

<!-- Navbar -->
<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="#"><i class="bi bi-speedometer2 me-2"></i>Панель управления</a>
        <a href="/admin/logout.php" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Выйти</a>
    </div>
</nav>

<div class="container">

    <!-- Welcome -->
    <div class="mb-4">
        <h1>Добро пожаловать, <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Гость') ?>!</h1>
        <p class="text-muted">Вы вошли в систему.</p>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title text-muted">Пользователи</h5>
                        <h2 class="display-6"><?= $user_count ?></h2>
                    </div>
                    <i class="bi bi-person-circle card-icon text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title text-muted">Заказы</h5>
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
                        <h5 class="card-title text-muted">Товары</h5>
                        <h2 class="display-6"><?= $product_count ?></h2>
                    </div>
                    <i class="bi bi-shop card-icon text-warning"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="mb-4">
        <h4>Быстрое меню</h4>
        <div class="row g-3">
            <div class="col-md-3">
                <a href="/admin/users/users_list.php" class="btn btn-outline-primary w-100 p-3">
                    <i class="bi bi-person-gear me-2"></i>Пользователи
                </a>
            </div>
            <div class="col-md-3">
                <a href="/admin/products/products_list.php" class="btn btn-outline-success w-100 p-3">
                    <i class="bi bi-cart me-2"></i>Товары
                </a>
            </div>
            <div class="col-md-3">
                <a href="/admin/orders/orders_list.php" class="btn btn-outline-info w-100 p-3">
                    <i class="bi bi-receipt me-2"></i>Заказы
                </a>
            </div>
            <div class="col-md-3">
                <a href="/admin/categories/categories_list.php" class="btn btn-outline-secondary w-100 p-3">
                    <i class="bi bi-tags me-2"></i>Категории
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Seller Requests -->
    <div class="mb-4">
        <h4>Последние заявки на регистрацию как продавец</h4>
        <?php if (empty($latest_requests)): ?>
            <div class="alert alert-info">Нет новых заявок.</div>
        <?php else: ?>
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th>ИНН</th>
                    <th>Сфера деятельности</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($latest_requests as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['id']) ?></td>
                        <td><?= htmlspecialchars($req['username']) ?></td>
                        <td><?= htmlspecialchars($req['inn']) ?></td>
                        <td><?= htmlspecialchars($req['activity_field']) ?></td>
                        <td>
                            <?php
                            switch ($req['status']) {
                                case 'pending': echo '<span class="badge bg-warning">Ожидает</span>'; break;
                                case 'approved': echo '<span class="badge bg-success">Одобрена</span>'; break;
                                case 'rejected': echo '<span class="badge bg-danger">Отклонена</span>'; break;
                                default: echo '<span class="badge bg-secondary">Неизвестно</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <a href="/admin/users/seller_requests.php" class="btn btn-sm btn-primary">Подробнее</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap @5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>