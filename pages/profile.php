<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль</title>

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
                    <h1 class="card-title mb-4">Профиль</h1>

                    <!-- Фото пользователя -->
                    <div class="profile-photo mb-4">
                        <?php if (!empty($user['profile_picture']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user['profile_picture'])): ?>
                            <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Фото пользователя" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <img src="/assets/images/default_profile.png" alt="Заглушка фото пользователя" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php endif; ?>
                    </div>

                    <!-- Информация о пользователе -->
                    <p class="mb-2"><strong>Имя пользователя:</strong> <?= htmlspecialchars($user['username']) ?></p>
                    <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p class="mb-4"><strong>Роль:</strong> <?= htmlspecialchars($user['role']) ?></p>

                    <!-- Кнопки -->
                    <a href="/pages/logout.php" class="btn btn-danger me-2 mb-2">
                        <i class="bi bi-box-arrow-right"></i> Выйти
                    </a>
                    <a href="/pages/edit-profile.php" class="btn btn-primary mb-2">
                        <i class="bi bi-pencil-square"></i> Редактировать профиль
                    </a>

                    <!-- Дополнительные действия -->
                    <div class="d-grid gap-2 col-10 mx-auto mt-3">
                        <a href="/pages/orders.php" class="btn btn-outline-primary">
                            <i class="bi bi-bag"></i> Мои покупки
                        </a>

                        <?php if ($user['role'] === 'seller'): ?>
                            <a href="/pages/my_products.php" class="btn btn-outline-success">
                                <i class="bi bi-shop"></i> Мои товары
                            </a>
                            <a href="/pages/seller_orders.php" class="btn btn-outline-secondary">
                                <i class="bi bi-receipt"></i> Заказы на мои товары
                            </a>
                        <?php elseif ($user['role'] === 'buyer'): ?>
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

                    <?php if (empty($orders)): ?>
                        <div class="alert alert-info">У вас пока нет заказов.</div>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($orders as $order): ?>
                                <li class="list-group-item">
                                    <a href="/pages/order_details.php?id=<?= $order['id'] ?>">
                                        Заказ #<?= htmlspecialchars($order['id']) ?> - <?= htmlspecialchars($order['status']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
