<?php
// pages/home.php

require_once __DIR__ . '/../includes/db.php'; // Подключаем db.php
require_once __DIR__ . '/../includes/functions.php'; // Подключаем functions.php

// Обработка поискового запроса
$searchQuery = trim($_GET['search'] ?? '');
$products = [];

try {
    if (!empty($searchQuery)) {
        // С поиском
        $stmt = $pdo->prepare("
            SELECT 
                p.*, 
                ROUND(AVG(r.rating), 1) AS average_rating, 
                COUNT(r.id) AS review_count
            FROM products p
            LEFT JOIN reviews r ON p.id = r.product_id
            WHERE p.name LIKE :search OR p.description LIKE :search
            GROUP BY p.id
        ");
        $stmt->execute(['search' => "%$searchQuery%"]);
    } else {
        // Без поиска
        $stmt = $pdo->query("
            SELECT 
                p.*, 
                ROUND(AVG(r.rating), 1) AS average_rating, 
                COUNT(r.id) AS review_count
            FROM products p
            LEFT JOIN reviews r ON p.id = r.product_id
            GROUP BY p.id
        ");
    }

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <style>
        .product-image {
            aspect-ratio: 4/3; /* или 1/1 для квадрата */
            width: 100%;
            object-fit: contain;
            background-color: #fff;
        }

        .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 180px;
        }
        
        @media (max-width: 767.98px) {
    #carouselExampleIndicators {
        display: none;
    }
}

    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container mt-4">

    <!-- Слайдер -->
    <div id="carouselExampleIndicators" class="carousel slide mb-4" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="/assets/images/yan.webp" class="d-block w-100 rounded-4" alt="Реклама 1">
            </div>
            <div class="carousel-item">
                <img src="/assets/images/hoff.webp" class="d-block w-100 rounded-4" alt="Реклама 2">
            </div>
            <div class="carousel-item">
                <img src="/assets/images/mv.webp" class="d-block w-100 rounded-4" alt="Реклама 3">
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
            <span class="carousel-control-prev-icon rounded-circle p-3" aria-hidden="true"></span>
            <span class="visually-hidden">Предыдущий</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
            <span class="carousel-control-next-icon rounded-circle p-3" aria-hidden="true"></span>
            <span class="visually-hidden">Следующий</span>
        </button>
    </div>

    <!-- Секция товаров -->
    <!-- Секция товаров -->
<section class="products">
    <h2 class="mb-3">Товары</h2>
    <div id="product-list" class="row g-4">
        <?php if (empty($products)): ?>
            <div class="col-12 text-center">
                <p class="text-muted">Товары не найдены.</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="/pages/product.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                        <div class="card h-100 shadow-sm border-0">
                            <?php if (!empty($product['main_image'])): ?>
                                <img src="<?= htmlspecialchars($product['main_image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="card-img-top rounded-3 product-image">
                            <?php else: ?>
                                <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="card-img-top rounded-3 product-image">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                <p class="card-text"><strong></strong> <?= htmlspecialchars($product['price']) ?> ₽</p>
                                <?php if ($product['review_count'] > 0): ?>
                                    <div class="mb-1 text-warning">
                                        <strong><?= $product['average_rating'] ?> ⭐</strong>
                                        (<?= $product['review_count'] ?> отзывов)
                                    </div>
                                <?php else: ?>
                                    <div class="mb-1 text-muted">Нет отзывов</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>