<?php
session_start();

require_once __DIR__ . '/../includes/db.php'; // Подключаем db.php
require_once __DIR__ . '/../includes/functions.php'; // Подключаем functions.php

// Получаем ID категории из GET-параметра
$category_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

if (!$category_id) {
    header("Location: /error.php?message=invalid_category_id");
    exit;
}

try {
    // Получаем название текущей категории
    $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        header("Location: /error.php?message=category_not_found");
        exit;
    }

    // Обработка фильтров
    $filters = [];
    $queryParams = [];

    // Фильтр по бренду
    if (isset($_GET['brand']) && is_numeric($_GET['brand'])) {
        $filters[] = "brand_id = :brand_id";
        $queryParams[':brand_id'] = (int)$_GET['brand'];
    }

    // Фильтр по цене (минимальная и максимальная)
    $min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (int)$_GET['min_price'] : null;
    $max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (int)$_GET['max_price'] : null;

    if ($min_price !== null) {
        $filters[] = "price >= :min_price";
        $queryParams[':min_price'] = $min_price;
    }
    if ($max_price !== null) {
        $filters[] = "price <= :max_price";
        $queryParams[':max_price'] = $max_price;
    }

    // Фильтр по распродаже
    if (isset($_GET['on_sale']) && $_GET['on_sale'] === 'true') {
        $filters[] = "on_sale = 1";
    }

    // Формируем базовый запрос
    $sql = "SELECT * FROM products WHERE category_id = :category_id";
    $queryParams[':category_id'] = $category_id;

    if (!empty($filters)) {
        $sql .= " AND " . implode(" AND ", $filters);
    }

    // Выполняем запрос
    $stmt = $pdo->prepare($sql);
    $stmt->execute($queryParams);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем список брендов для фильтрации
    $stmt = $pdo->prepare("SELECT DISTINCT brand_id, b.name AS brand_name 
                           FROM products p
                           JOIN brands b ON p.brand_id = b.id
                           WHERE p.category_id = ?
                           ORDER BY b.name ASC");
    $stmt->execute([$category_id]);
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Товары категории: <?= htmlspecialchars($category['name']) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Собственные стили -->
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>

    <main class="container mt-4">
        <h1 class="mb-4">Товары категории: <?= htmlspecialchars($category['name']) ?></h1>

        <div class="row">
            <!-- Левая колонка: Фильтры -->
            <div class="col-md-3">
                <form id="filter-form" method="GET" action="">
                    <input type="hidden" name="id" value="<?= $category_id ?>">

                    <!-- Фильтр по бренду -->
                    <div class="mb-3">
                        <h5>Бренд</h5>
                        <?php foreach ($brands as $brand): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="brand[]" value="<?= $brand['brand_id'] ?>" id="brand_<?= $brand['brand_id'] ?>"
                                    <?= isset($_GET['brand']) && in_array($brand['brand_id'], (array)$_GET['brand']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="brand_<?= $brand['brand_id'] ?>">
                                    <?= htmlspecialchars($brand['brand_name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Фильтр по цене -->
                    <div class="mb-3">
                        <h5>Цена</h5>
                        <div class="input-group mb-2">
                            <span class="input-group-text">От</span>
                            <input type="number" class="form-control" name="min_price" value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>" min="0">
                        </div>
                        <div class="input-group">
                            <span class="input-group-text">До</span>
                            <input type="number" class="form-control" name="max_price" value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>" min="0">
                        </div>
                    </div>

                    <!-- Фильтр по распродаже -->
                    <div class="mb-3">
                        <h5>Распродажа</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="on_sale" id="on_sale" value="true"
                                <?= isset($_GET['on_sale']) && $_GET['on_sale'] === 'true' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="on_sale">
                                Только товары со скидкой
                            </label>
                        </div>
                    </div>

                    <!-- Кнопка применения фильтров -->
                    <button type="submit" class="btn btn-primary w-100">Применить фильтры</button>
                </form>
            </div>

            <!-- Правая колонка: Товары -->
            <div class="col-md-9">
                <?php if (!empty($products)): ?>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-4 mb-4">
                                <a href="/pages/product.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                                    <div class="card h-100">
                                        <?php if (!empty($product['main_image'])): ?>
                                            <img src="<?= htmlspecialchars($product['main_image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="card-img-top">
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
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        Нет товаров, соответствующих выбранным фильтрам.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>