<?php
// pages/product.php

session_start();
require '../includes/db.php';
require '../includes/functions.php';

$product_id = $_GET['id'] ?? null;

if ($product_id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$product) {
    die("Товар не найден.");
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <h1><?= htmlspecialchars($product['name']) ?></h1>

        <!-- Вывод изображения товара -->
        <?php if (!empty($product['image'])): ?>
            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-page">
        <?php else: ?>
            <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="product-page">
        <?php endif; ?>

        <p><?= htmlspecialchars($product['description']) ?></p>
        <p class="price">Цена: <?= htmlspecialchars($product['price']) ?> руб.</p>
        <a href="/pages/cart.php?action=add&id=<?= $product['id'] ?>" class="btn">Добавить в корзину</a>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>