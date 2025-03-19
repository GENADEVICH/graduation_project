<?php
// pages/home.php

require_once __DIR__ . '/../includes/db.php'; // Подключаем db.php
require_once __DIR__ . '/../includes/functions.php'; // Подключаем functions.php

// Обработка поискового запроса
$searchQuery = trim($_GET['search'] ?? ''); // Получаем поисковый запрос
$products = [];

try {
    if (!empty($searchQuery)) {
        // Если есть поисковый запрос, фильтруем товары
        $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE :search OR description LIKE :search");
        $stmt->execute(['search' => "%$searchQuery%"]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Если запроса нет, выводим все товары
        $stmt = $pdo->query("SELECT * FROM products");
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
    <title>Главная страница</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons (опционально) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Твои собственные стили -->
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mt-4">
        <h1 class="text-center mb-4">Добро пожаловать в наш маркетплейс!</h1>
        <p class="text-center lead">Здесь вы найдете лучшие товары по выгодным ценам.</p>

        <!-- Секция для отображения товаров -->
        <section class="products">
            <h2 class="mb-3">Товары</h2>
            <div id="product-list" class="row g-4">
                <?php if (empty($products)): ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">Товары не найдены.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-4 col-lg-3">
                            <a href="/pages/product.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                                <div class="card h-100">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="card-img-top product-image">
                                    <?php else: ?>
                                        <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="card-img-top product-image">
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
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <!-- Bootstrap JS и зависимости -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>