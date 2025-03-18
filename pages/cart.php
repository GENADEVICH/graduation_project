<?php
// pages/cart.php

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
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <h1>Корзина</h1>

        <?php if (empty($_SESSION['cart'])): ?>
            <p>Ваша корзина пуста.</p>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <div class="cart-item">
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-item-image">
                        <?php else: ?>
                            <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="cart-item-image">
                        <?php endif; ?>
                        <div class="cart-item-details">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="price">Цена: <?= htmlspecialchars($item['price']) ?> руб.</p>
                            <p>Количество: <?= htmlspecialchars($item['quantity']) ?></p>
                            <p>Сумма: <?= htmlspecialchars($item['price'] * $item['quantity']) ?> руб.</p>
                            <a href="/pages/cart.php?action=remove&id=<?= $item['id'] ?>" class="btn btn-danger">Удалить</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="cart-total">
                <h3>Общая сумма: <?= htmlspecialchars($total) ?> руб.</h3>
                <a href="/pages/cart.php?action=clear" class="btn btn-clear">Очистить корзину</a>
            </div>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>