<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require '../includes/db.php';
require '../includes/functions.php';

// Проверка авторизации
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user_id = $_SESSION['user_id'];

// Обработка действий с корзиной
$action = $_GET['action'] ?? null;
$product_id = $_GET['id'] ?? null;

if ($action === 'add' && $product_id) {
    // Добавление товара в корзину
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
    $stmt->execute([$user_id, $product_id]);
    redirect('/pages/cart.php');
} elseif ($action === 'remove' && $product_id) {
    // Удаление товара из корзины
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    redirect('/pages/cart.php');
} elseif ($action === 'clear') {
    // Очистка корзины
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    redirect('/pages/cart.php');
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    // Обновление количества товаров
    foreach ($_POST['quantities'] as $product_id => $quantity) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([max(1, intval($quantity)), $user_id, $product_id]);
    }
    redirect('/pages/cart.php');
}

// Получение товаров из корзины
$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.price, p.main_image, c.quantity
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Подсчет общей суммы
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Твои собственные стили -->
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container mt-4">
        <div style="height: 1220px">
            <h1 class="text-center mb-4">Корзина</h1>

            <?php if (empty($cartItems)): ?>
                <div class="alert alert-info text-center">
                    Ваша корзина пуста.
                </div>
            <?php else: ?>
                <form method="POST" action="/pages/cart.php">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">Изображение</th>
                                <th scope="col">Наименование</th>
                                <th scope="col">Цена</th>
                                <th scope="col">Количество</th>
                                <th scope="col">Сумма</th>
                                <th scope="col">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($item['main_image'])): ?>
                                            <img src="<?= htmlspecialchars($item['main_image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="img-fluid rounded" style="max-width: 50px;">
                                        <?php else: ?>
                                            <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="img-fluid rounded" style="max-width: 50px;">
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td><?= htmlspecialchars($item['price']) ?> руб.</td>
                                    <td>
                                        <input type="number" name="quantities[<?= $item['id'] ?>]" value="<?= htmlspecialchars($item['quantity']) ?>" min="1" class="form-control" style="width: 80px;">
                                    </td>
                                    <td><?= htmlspecialchars($item['price'] * $item['quantity']) ?> руб.</td>
                                    <td>
                                        <a href="/pages/cart.php?action=remove&id=<?= $item['id'] ?>" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash"></i> Удалить
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Общая сумма: <?= htmlspecialchars($total) ?> руб.</h3>
                        <div>
                            <button type="submit" name="update_cart" class="btn btn-success me-2">
                                <i class="bi bi-check-circle"></i> Обновить корзину
                            </button>
                            <a href="/pages/cart.php?action=clear" class="btn btn-warning me-2">
                                <i class="bi bi-trash"></i> Очистить корзину
                            </a>
                            <a href="/pages/checkout.php" class="btn btn-primary">
                                <i class="bi bi-credit-card"></i> Оформить заказ
                            </a>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <!-- Bootstrap JS и зависимости -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>