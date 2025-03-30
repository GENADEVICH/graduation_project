<?php
session_start();

require_once __DIR__ . '/../includes/db.php'; // Подключаем db.php
require_once __DIR__ . '/../includes/functions.php'; // Подключаем functions.php

// Получаем ID бренда из GET-параметра
$brand_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

if (!$brand_id) {
    header("Location: /error.php?message=invalid_brand_id");
    exit;
}

try {
    // Получаем информацию о бренде
    $stmt = $pdo->prepare("SELECT id, name FROM brands WHERE id = ?");
    $stmt->execute([$brand_id]);
    $brand = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$brand) {
        header("Location: /error.php?message=brand_not_found");
        exit;
    }

    // Получаем товары выбранного бренда
    $stmt = $pdo->prepare("SELECT * FROM products WHERE brand_id = ?");
    $stmt->execute([$brand_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header("Location: /error.php?message=database_error&details=" . urlencode($e->getMessage()));
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Товары бренда <?= htmlspecialchars($brand['name']) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Собственные стили -->
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>

    <main class="container mt-4">
        <h1 class="mb-3">Товары бренда <?= htmlspecialchars($brand['name']) ?></h1>

        <?php if (!empty($products)): ?>
            <div class="row">
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="card-img-top">
                            <?php else: ?>
                                <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="card-img-top">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                <p class="card-text flex-grow-1"><?= htmlspecialchars($product['description']) ?></p>
                                <p class="card-text text-primary"><strong>Цена:</strong> <?= htmlspecialchars($product['price']) ?> руб.</p>
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
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                Нет товаров для данного бренда.
            </div>
        <?php endif; ?>
    </main>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>