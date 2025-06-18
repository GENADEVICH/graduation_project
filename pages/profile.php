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
$stmt = $pdo->prepare("SELECT username, email, profile_picture FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Проверка наличия пользователя
if (!$user) {
    $errors['general'] = "Пользователь не найден.";
}

// Получение роли из сессии
$role = $_SESSION['role'] ?? null;

if (!$role) {
    $stmtRole = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmtRole->execute([$user_id]);
    $user_role = $stmtRole->fetchColumn();
    $role = $user_role ?: 'buyer'; // роль по умолчанию
    $_SESSION['role'] = $role;
}

// Получение списка заказов пользователя
$stmt_orders = $pdo->prepare("SELECT * FROM orders WHERE buyer_id = ? ORDER BY order_date DESC");
$stmt_orders->execute([$user_id]);
$orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

// Получаем id всех заказов для выборки товаров
$order_ids = array_column($orders, 'id');
$order_items = [];

if (!empty($order_ids)) {
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $stmt_items = $pdo->prepare("
        SELECT oi.order_id, p.name 
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id IN ($placeholders)
    ");
    $stmt_items->execute($order_ids);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $order_items[$item['order_id']][] = $item['name'];
    }
}

// Разделение заказов на актуальные и завершённые
$active_orders = [];
$completed_orders = [];

foreach ($orders as $order) {
    // Актуальные заказы (pending, shipped, paid)
    if (in_array($order['status'], ['pending', 'shipped', 'paid'])) {
        $active_orders[] = $order;
    }
    // Завершённые заказы (completed, cancelled, delivered)
    elseif (in_array($order['status'], ['completed', 'cancelled', 'delivered'])) {
        $completed_orders[] = $order;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Профиль</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/styles.css" />
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container mt-4">
    <div class="row justify-content-center">
        <!-- Левый блок с информацией о пользователе -->
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-body text-center">
                    <h1 class="card-title mb-4">Профиль</h1>

                    <!-- Фото пользователя -->
                    <div class="profile-photo mb-4">
                        <?php if (!empty($user['profile_picture']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user['profile_picture'])): ?>
                            <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Фото пользователя" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;" />
                        <?php else: ?>
                            <img src="/assets/images/default_profile.png" alt="Заглушка фото пользователя" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;" />
                        <?php endif; ?>
                    </div>

                    <!-- Информация о пользователе -->
                    <p class="mb-2"><strong>Имя пользователя:</strong> <?= htmlspecialchars($_SESSION['username'] ?? 'Гость') ?></p>
                    <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>

                    <!-- Кнопки -->
                    <a href="/pages/logout.php" class="btn btn-danger me-2 mb-2">
                        <i class="bi bi-box-arrow-right"></i> Выйти
                    </a>
                    <a href="/pages/edit-profile.php" class="btn btn-primary mb-2">
                        <i class="bi bi-pencil-square"></i> Редактировать профиль
                    </a>
                    <?php if ($role === 'admin'): ?>
                        <a href="/admin/dashboard.php" class="btn btn-primary mb-2">
                            <i class="bi bi-database"></i> Admin
                        </a>
                    <?php endif; ?>

                    <!-- Дополнительные действия -->
                    <div class="d-grid gap-2 col-10 mx-auto mt-3">
                        <?php if ($role === 'seller'): ?>
                            <a href="/seller/dashboard.php" class="btn btn-primary mb-2">
                                <i class="bi bi-shop"></i> Личный кабинет
                            </a>
                        <?php elseif ($role === 'buyer'): ?>
                            <a href="/pages/become_seller.php" class="btn btn-warning">
                                <i class="bi bi-person-plus"></i> Стать продавцом
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Правый блок с заказами -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Мои заказы</h5>

                    <!-- Вкладки -->
                    <ul class="nav nav-tabs" id="orderTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="active-tab" data-bs-toggle="tab" href="#active-orders" role="tab" aria-controls="active-orders" aria-selected="true">Актуальные</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="completed-tab" data-bs-toggle="tab" href="#completed-orders" role="tab" aria-controls="completed-orders" aria-selected="false">Завершённые</a>
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
                                                Заказ #<?= htmlspecialchars($order['order_number']) ?> - <?= translateOrderStatus($order['status']) ?>
                                            </a>
                                            <br />
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
                                                Заказ #<?= htmlspecialchars($order['order_number']) ?> - <?= translateOrderStatus($order['status']) ?>
                                            </a>
                                            <br />
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <!-- Блок поддержки -->
<div class="card mt-4">
    <div class="card-body">
        <h5 class="card-title"><i class="bi bi-life-preserver"></i> Техническая поддержка</h5>
        <p class="card-text">Если у вас возникли вопросы или проблемы, свяжитесь с нашей службой поддержки:</p>
        <ul class="list-unstyled">
            <li><i class="bi bi-envelope"></i> <strong>Email:</strong> <a href="mailto:support@akrapov1c.ru">support@akrapov1c.ru</a></li>
            <li><i class="bi bi-clock"></i> <strong>Время работы:</strong> Пн–Пт с 9:00 до 18:00</li>
        </ul>
        <a href="/pages/support_form.php" class="btn btn-outline-primary mt-2">
            <i class="bi bi-chat-dots"></i> Написать в поддержку
        </a>
    </div>
</div>

    </div>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Функция для перевода статусов заказов
function translateOrderStatus($status) {
    switch ($status) {
        case 'pending':
            return 'Ожидает подтверждения';
        case 'shipped':
            return 'Отправлен';
        case 'paid':
            return 'Оплачен';
        case 'completed':
            return 'Завершён';
        case 'cancelled':
            return 'Отменён';
        case 'delivered':
            return 'Доставлен';
        default:
            return 'Неизвестный статус';
    }
}
?>
