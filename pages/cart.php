<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

// Инициализация корзины, если она еще не существует
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Обработка действий с корзиной
$action = $_GET['action'] ?? null;
$product_id = $_GET['id'] ?? null;

if ($action === 'add' && $product_id) {
    // Добавление товара в корзину
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        if (isset($_SESSION['cart'][$product_id])) {
            // Если товар уже в корзине, увеличиваем количество
            $_SESSION['cart'][$product_id]['quantity'] += 1;
        } else {
            // Если товара нет в корзине, добавляем его
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => 1,
                'image' => $product['image']
            ];
        }
    }
    redirect('/pages/cart.php');
} elseif ($action === 'remove' && $product_id) {
    // Удаление товара из корзины
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
    redirect('/pages/cart.php');
} elseif ($action === 'clear') {
    // Очистка корзины
    $_SESSION['cart'] = [];
    redirect('/pages/cart.php');
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    // Обновление количества товаров
    foreach ($_POST['quantities'] as $product_id => $quantity) {
        if (isset($_SESSION['cart'][$product_id])) {
            // Обновляем количество, если оно больше 0
            $_SESSION['cart'][$product_id]['quantity'] = max(1, intval($quantity));
        }
    }
    redirect('/pages/cart.php');
}

// Подсчет общей суммы
$total = 0;
foreach ($_SESSION['cart'] as $item) {
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

        <?php if (empty($_SESSION['cart'])): ?>
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
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="img-fluid rounded" style="max-width: 50px;">
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