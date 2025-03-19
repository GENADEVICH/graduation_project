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

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons (опционально) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Твои собственные стили -->
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container mt-4">
        <div class="row">
            <!-- Левая колонка: Изображение товара -->
            <div class="col-md-6 mb-4">
                <?php if (!empty($product['image'])): ?>
                    <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="img-fluid rounded">
                <?php else: ?>
                    <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="img-fluid rounded">
                <?php endif; ?>
            </div>

            <!-- Правая колонка: Информация о товаре -->
            <div class="col-md-6">
                <h1 class="mb-3"><?= htmlspecialchars($product['name']) ?></h1>
                <p class="lead"><?= htmlspecialchars($product['description']) ?></p>
                <p class="h4 text-primary">Цена: <?= htmlspecialchars($product['price']) ?> руб.</p>

                <!-- Кнопка "Добавить в корзину" -->
                <a href="/pages/cart.php?action=add&id=<?= $product['id'] ?>" class="btn btn-primary btn-lg">
                    <i class="bi bi-cart-plus"></i> Добавить в корзину
                </a>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS и зависимости -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>