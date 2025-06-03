<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}

// Получаем статистику
try {
    // Общее количество пользователей
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();

    // Общее количество заказов
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $order_count = $stmt->fetchColumn();

    // Общее количество товаров
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $product_count = $stmt->fetchColumn();

    // Последние заявки на регистрацию как продавец
    $stmt = $pdo->query("SELECT s.*, u.username, u.email FROM sellers s JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT 5");
    $latest_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Активность новых пользователей за последние 7 дней
    $stmt = $pdo->query("
        SELECT DATE(created_at) AS date, COUNT(*) AS count 
        FROM users 
        WHERE created_at >= CURDATE() - INTERVAL 7 DAY 
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at)
    ");
    $user_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Выручка за последние 7 дней
    $stmt = $pdo->query("
        SELECT DATE(order_date) AS date, SUM(total_price) AS total 
        FROM orders 
        WHERE order_date >= CURDATE() - INTERVAL 7 DAY 
        GROUP BY DATE(order_date)
        ORDER BY DATE(order_date)
    ");
    $revenue_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Топ 5 товаров по количеству в заказах
    $stmt = $pdo->query("
        SELECT p.id, p.name, SUM(oi.quantity) AS total_sold
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        canvas {
            max-width: 100%;
            height: 180px !important;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="#"><i class="bi bi-speedometer2 me-2"></i>Панель управления</a>
        <a href="/pages/profile.php" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Выйти</a>
    </div>
</nav>

<div class="container">

    <!-- Welcome -->
    <div class="mb-4">
        <h1>Добро пожаловать, <?= htmlspecialchars($_SESSION['user']['username']) ?>!</h1>
        <p class="text-muted">Вы вошли как администратор.</p>
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
                    <i class="bi bi-receipt me-2"></i> Заказы
                </a>
            </div>
            <div class="col-md-3">
                <a href="/admin/categories/categories_list.php" class="btn btn-outline-secondary w-100 p-3">
                    <i class="bi bi-diagram-3 me-2"></i>Категории
                </a>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Активность пользователей (7 дней)</h5>
                    <canvas id="userActivityChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Выручка (7 дней)</h5>
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="mb-4">
        <h4>ТОП товаров по продажам</h4>
        <?php if (empty($top_products)): ?>
            <p class="text-muted">Нет данных о продажах.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($top_products as $product): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($product['name']) ?>
                        <span class="badge bg-success rounded-pill"><?= $product['total_sold'] ?> шт.</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
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
                                    case 'pending':
                                        echo '<span class="badge bg-warning text-dark">Ожидает</span>';
                                        break;
                                    case 'approved':
                                        echo '<span class="badge bg-success">Одобрена</span>';
                                        break;
                                    case 'rejected':
                                        echo '<span class="badge bg-danger">Отклонена</span>';
                                        break;
                                    default:
                                        echo '<span class="badge bg-secondary">Неизвестно</span>';
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

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js "></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // График активности пользователей
    const userCtx = document.getElementById('userActivityChart').getContext('2d');
    const userLabels = [<?php foreach ($user_activity as $row) echo '"' . $row['date'] . '",'; ?>];
    const userData = [<?php foreach ($user_activity as $row) echo $row['count'] . ','; ?>];

    new Chart(userCtx, {
        type: 'line',
        data: {
            labels: userLabels,
            datasets: [{
                label: 'Новые пользователи',
                data: userData,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // График выручки
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueLabels = [<?php foreach ($revenue_data as $row) echo '"' . $row['date'] . '",'; ?>];
    const revenueData = [<?php foreach ($revenue_data as $row) echo $row['total'] . ','; ?>];

    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Выручка (₽)',
                data: revenueData,
                backgroundColor: '#198754'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return value + ' ₽';
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>