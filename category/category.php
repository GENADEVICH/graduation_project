<?php
// pages/category.php
$categoryId = (int)($_GET['id'] ?? 0);

require_once __DIR__ . '/../includes/db.php'; // Подключаем db.php
require_once __DIR__ . '/../includes/functions.php'; // Подключаем functions.php

// Получаем ID категории из GET-параметра
$categoryId = (int)($_GET['id'] ?? 0);

if ($categoryId <= 0) {
    die("Неверный ID категории.");
}

try {
    // Получаем название текущей категории
    $stmt = $pdo->prepare("SELECT id, name, image_url FROM categories WHERE id = :id");
    $stmt->execute(['id' => $categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        die("Категория не найдена.");
    }

    // Получаем подкатегории текущей категории
    $stmt = $pdo->prepare("SELECT id, name, image_url FROM categories WHERE parent_id = :parent_id");
    $stmt->execute(['parent_id' => $categoryId]);
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем популярные бренды текущей категории
    $stmt = $pdo->prepare("
        SELECT b.id, b.name
        FROM brands b
        WHERE b.category_id = :category_id
        ORDER BY b.name ASC
        LIMIT 5
    ");
    $stmt->execute(['category_id' => $categoryId]);
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем товары текущей категории (если нет подкатегорий)
    $products = [];
    if (empty($subcategories)) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = :category_id");
        $stmt->execute(['category_id' => $categoryId]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Ошибка при выполнении запроса: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Категория: <?= htmlspecialchars($category['name']) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Твои собственные стили -->
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mt-4">
        <h1 class="mb-3"><?= htmlspecialchars($category['name']) ?></h1>

        <!-- Изображение категории
        <?php if (!empty($category['image_url'])): ?>
            <div class="mb-4 text-center">
                <img src="<?= htmlspecialchars($category['image_url']) ?>" alt="<?= htmlspecialchars($category['name']) ?>" class="img-fluid rounded-3" style="max-height: 300px;">
            </div>
        <?php endif; ?> -->

        <!-- Подкатегории -->
        <?php if (!empty($subcategories)): ?>
            <section class="mb-4">
                <h2 class="mb-3">Популярные категории</h2>
                <div class="row">
                    <?php foreach ($subcategories as $subcategory): ?>
                        <div class="col-md-4 col-lg-3 mb-3">
                            <a href="/category/category.php?id=<?= $subcategory['id'] ?>" class="text-decoration-none text-dark">
                                <div class="card h-100">
                                    <?php if (!empty($subcategory['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($subcategory['image_url']) ?>" alt="<?= htmlspecialchars($subcategory['name']) ?>" class="card-img-top rounded-3">
                                    <?php else: ?>
                                        <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="card-img-top rounded-3">
                                    <?php endif; ?>
                                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                                        <h5 class="card-title"><?= htmlspecialchars($subcategory['name']) ?></h5>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Популярные бренды -->
        <?php if (!empty($brands)): ?>
            <section class="mb-4">
                <h2 class="mb-3">Популярные бренды</h2>
                <div class="d-flex gap-3 flex-wrap">
                    <?php foreach ($brands as $brand): ?>
                        <a href="/pages/brand.php?id=<?= $brand['id'] ?>" class="btn btn-outline-primary">
                            <?= htmlspecialchars($brand['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Товары -->
        <?php if (!empty($products)): ?>
            <section class="products">
                <h2 class="mb-3">Товары</h2>
                <div id="product-list" class="row g-4">
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-4 col-lg-3">
                            <a href="/pages/product.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                                <div class="card h-100">
                                    <?php if (!empty($product['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="card-img-top rounded-3">
                                    <?php else: ?>
                                        <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="card-img-top rounded-3">
                                    <?php endif; ?>
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                        <p class="card-text flex-grow-1"><?= htmlspecialchars($product['description']) ?></p>
                                        <p class="card-text"><strong>Цена:</strong> <?= htmlspecialchars($product['price']) ?> руб.</p>
                                        <div class="d-flex gap-2 mt-auto">
                                            <a href="/pages/cart.php?action=add&id=<?= $product['id'] ?>" class="btn btn-success btn-sm flex-fill">
                                                <i class="bi bi-cart-plus"></i> В корзину
                                            </a>
                                            <a href="/pages/wishlist.php?action=add&id=<?= $product['id'] ?>" class="btn btn-outline-danger btn-sm flex-fill">
                                                <i class="bi bi-heart"></i> В избранное
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif (empty($subcategories)): ?>
            <div class="col-12 text-center">
                <p class="text-muted">Товары в этой категории не найдены.</p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Bootstrap JS и зависимости -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>