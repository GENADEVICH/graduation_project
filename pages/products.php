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

    $queryParams = [];
    $filters = [];

    // Фильтр по брендам (массив)
    if (isset($_GET['brand']) && is_array($_GET['brand'])) {
        $brand_ids = array_filter($_GET['brand'], 'is_numeric');
        if (!empty($brand_ids)) {
            $placeholders = implode(',', array_fill(0, count($brand_ids), '?'));
            $filters[] = "brand_id IN ($placeholders)";
            foreach ($brand_ids as $id) {
                $queryParams[] = (int)$id;
            }
        }
    }

    // Фильтр по цене
    if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
        $filters[] = "price >= ?";
        $queryParams[] = (int)$_GET['min_price'];
    }
    if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
        $filters[] = "price <= ?";
        $queryParams[] = (int)$_GET['max_price'];
    }

    // Фильтр по распродаже
    if (isset($_GET['on_sale']) && $_GET['on_sale'] === 'true') {
        $filters[] = "on_sale = 1";
    }

    // Базовый запрос
    $sql = "SELECT * FROM products WHERE category_id = ?";
    array_unshift($queryParams, $category_id); // добавляем category_id в начало параметров

    if (!empty($filters)) {
        $sql .= " AND " . implode(" AND ", $filters);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($queryParams);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем список брендов для фильтрации
    $stmt = $pdo->prepare("SELECT DISTINCT b.id AS brand_id, b.name AS brand_name 
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Товары категории: <?= htmlspecialchars($category['name']) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

    <!-- Собственные стили -->
    <link rel="stylesheet" href="/assets/css/styles.css" />

    <style>
        /* Сделаем описание товаров немного короче, чтобы карточки были ровнее по высоте */
        .card-text.flex-grow-1 {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3; /* Показывать максимум 3 строки */
            -webkit-box-orient: vertical;
        }
    </style>
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>

    <main class="container mt-4">
        <h1 class="mb-4">Товары категории: <?= htmlspecialchars($category['name']) ?></h1>

        <div class="row">
            <!-- Левая колонка: Фильтры -->
            <aside class="col-md-2 mb-4 border">
                <form id="filter-form" method="GET" action="">
                    <input type="hidden" name="id" value="<?= $category_id ?>" />

                    <!-- Фильтр по бренду -->
                    <div class="mb-3">
                        <h5>Бренд</h5>
                        <?php foreach ($brands as $brand): ?>
                            <div class="form-check">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    name="brand[]" 
                                    value="<?= (int)$brand['brand_id'] ?>" 
                                    id="brand_<?= (int)$brand['brand_id'] ?>"
                                    <?= (isset($_GET['brand']) && is_array($_GET['brand']) && in_array((string)$brand['brand_id'], $_GET['brand'], true)) ? 'checked' : '' ?>
                                >
                                <label class="form-check-label" for="brand_<?= (int)$brand['brand_id'] ?>">
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
                            <input type="number" min="0" class="form-control" name="min_price" value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>" />
                        </div>
                        <div class="input-group">
                            <span class="input-group-text">До</span>
                            <input type="number" min="0" class="form-control" name="max_price" value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>" />
                        </div>
                    </div>

                    <!-- Фильтр по распродаже -->
                    <div class="mb-3">
                        <h5>Распродажа</h5>
                        <div class="form-check">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                name="on_sale" 
                                id="on_sale" 
                                value="true"
                                <?= (isset($_GET['on_sale']) && $_GET['on_sale'] === 'true') ? 'checked' : '' ?>
                            />
                            <label class="form-check-label" for="on_sale">
                                Только товары со скидкой
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Применить фильтры</button>
                </form>
            </aside>

            <!-- Правая колонка: Товары -->
            <section class="col-md-10">
                <?php if (!empty($products)): ?>
                    <div class="row g-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-3">
                                <a href="/pages/product.php?id=<?= (int)$product['id'] ?>" class="text-decoration-none text-dark">
                                    <div class="card h-100">
                                        <?php if (!empty($product['main_image'])): ?>
                                            <img src="<?= htmlspecialchars($product['main_image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="card-img-top" />
                                        <?php else: ?>
                                            <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="card-img-top" />
                                        <?php endif; ?>
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                            <p class="card-text flex-grow-1"><?= htmlspecialchars($product['description']) ?></p>
                                            <p class="card-text text-primary"><strong>Цена:</strong> <?= htmlspecialchars($product['price']) ?> руб.</p>
                                            <div class="d-flex gap-2 mt-auto">
                                                <a href="/pages/cart.php?action=add&id=<?= (int)$product['id'] ?>" class="btn btn-success btn-sm flex-fill">
                                                    <i class="bi bi-cart-plus"></i> В корзину
                                                </a>
                                                <a href="/pages/wishlist.php?action=add&id=<?= (int)$product['id'] ?>" class="btn btn-outline-danger btn-sm flex-fill">
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
            </section>
        </div>
    </main>

    <!-- Bootstrap JS и зависимости -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
