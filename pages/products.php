<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$category_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
if (!$category_id) {
    header("Location: /error.php?message=invalid_category_id");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$category) {
        header("Location: /error.php?message=category_not_found");
        exit;
    }

    $queryParams = [];
    $filters = [];

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

    if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
        $filters[] = "price >= ?";
        $queryParams[] = (int)$_GET['min_price'];
    }

    if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
        $filters[] = "price <= ?";
        $queryParams[] = (int)$_GET['max_price'];
    }

    if (isset($_GET['on_sale']) && $_GET['on_sale'] === 'true') {
        $filters[] = "on_sale = 1";
    }

    $sort = $_GET['sort'] ?? '';

    if ($sort === 'rating') {
        $sql = "SELECT p.*, COALESCE(AVG(r.rating), 0) AS avg_rating 
                FROM products p
                LEFT JOIN reviews r ON p.id = r.product_id";
    } else {
        $sql = "SELECT p.* FROM products p";
    }

    $sql .= " WHERE p.category_id = ?";
    array_unshift($queryParams, $category_id);

    if (!empty($filters)) {
        $sql .= " AND " . implode(" AND ", $filters);
    }

    if ($sort === 'rating') {
        $sql .= " GROUP BY p.id";
    }

    switch ($sort) {
        case 'newest':
            $sql .= " ORDER BY p.created_at DESC";
            break;
        case 'low_price':
            $sql .= " ORDER BY p.price ASC";
            break;
        case 'high_price':
            $sql .= " ORDER BY p.price DESC";
            break;
        case 'rating':
            $sql .= " ORDER BY avg_rating DESC";
            break;
        default:
            $sql .= " ORDER BY p.id DESC";
            break;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($queryParams);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"  rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"  rel="stylesheet" />
    <!-- Собственные стили -->
    <link rel="stylesheet" href="/assets/css/styles.css" />
    <style>
        /* Адаптация под мобильные */
        .card-img-top {
            height: 140px;
            object-fit: contain;
        }
        .card-title {
            font-size: 0.9rem;
        }
        .card-text.text-primary strong {
            font-size: 1rem;
        }
        .form-check-label {
            font-size: 0.85rem;
        }
        #filter-form {
            margin-bottom: 1rem;
        }
        .btn-lg {
            padding: 0.6rem 1rem;
            font-size: 0.95rem;
        }
        .btn-custom-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    <main class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h1 class="mb-0"><?= htmlspecialchars($category['name']) ?></h1>
            <select id="sort" class="form-select w-auto">
                <option value="" <?= empty($sort) ? 'selected' : '' ?>>Популярные</option>
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Новинки</option>
                <option value="low_price" <?= $sort === 'low_price' ? 'selected' : '' ?>>Дешевле</option>
                <option value="high_price" <?= $sort === 'high_price' ? 'selected' : '' ?>>Дороже</option>
                <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>С высоким рейтингом</option>
            </select>
        </div>

        <!-- Кнопка фильтров на мобильных -->
        <button class="btn btn-outline-secondary d-md-none mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse">
            <i class="bi bi-funnel"></i> Фильтры
        </button>

        <div class="row">
            <!-- Фильтры -->
            <aside class="col-md-2 mb-4 border collapse d-md-block" id="filtersCollapse">
                <form id="filter-form" method="GET" action="">
                    <input type="hidden" name="id" value="<?= $category_id ?>" />
                    <!-- Бренды -->
                    <div class="mb-3">
                        <h5>Бренд</h5>
                        <?php foreach ($brands as $brand): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="brand[]" value="<?= $brand['brand_id'] ?>" id="brand_<?= $brand['brand_id'] ?>"
                                    <?= (isset($_GET['brand']) && in_array((string)$brand['brand_id'], $_GET['brand'])) ? 'checked' : '' ?> />
                                <label class="form-check-label" for="brand_<?= $brand['brand_id'] ?>">
                                    <?= htmlspecialchars($brand['brand_name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Цена -->
                    <div class="mb-3">
                        <h5>Цена</h5>
                        <div class="input-group mb-2">
                            <span class="input-group-text">От</span>
                            <input type="number" min="0" class="form-control" name="min_price" value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>" />
                        </div>
                        <div class="input-group mb-3">
                            <span class="input-group-text">До</span>
                            <input type="number" min="0" class="form-control" name="max_price" value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>" />
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Применить</button>
                    </div>
                </form>
            </aside>

            <!-- Товары -->
            <section class="col-md-10">
                <?php if (!empty($products)): ?>
                    <div class="row g-3">
                        <?php foreach ($products as $product): ?>
                            <div class="col-6 col-sm-6 col-md-3 position-relative">
                                <a href="/pages/product.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                                    <div class="card h-100">
                                        <div class="position-absolute top-0 end-0 m-1 z-2">
                                            <button class="btn btn-outline-danger add-to-wishlist" data-product-id="<?= $product['id'] ?>">
                                                <i class="bi bi-heart"></i>
                                            </button>
                                        </div>
                                        <img src="<?= !empty($product['main_image']) ? htmlspecialchars($product['main_image']) : '/assets/images/no-image.jpg' ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="card-img-top" />
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                            <p class="card-text text-primary"><strong><?= htmlspecialchars($product['price']) ?> ₽</strong></p>
                                            <!-- Уменьшенная кнопка "В корзину" -->
                                            <a href="/pages/cart.php?action=add&id=<?= (int)$product['id'] ?>" class="btn btn-success btn-sm mt-auto">
                                                <i class="bi bi-cart-plus me-1"></i> В корзину
                                            </a>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        Нет товаров по выбранным фильтрам.
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> 
    <!-- JS для сортировки -->
    <script>
        document.getElementById('sort').addEventListener('change', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const selectedSort = this.value;
            if (selectedSort) urlParams.set('sort', selectedSort);
            else urlParams.delete('sort');
            window.location.search = urlParams.toString();
        });
    </script>
    <!-- Wishlist AJAX -->
    <script>
        document.querySelectorAll('.add-to-wishlist').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.productId;
                const icon = this.querySelector('i');
                fetch('/api/add_to_wishlist_ajax.php?id=' + productId, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ remove: icon.classList.contains('bi-heart-fill') })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (icon.classList.contains('bi-heart')) {
                            icon.classList.remove('bi-heart');
                            icon.classList.add('bi-heart-fill', 'text-white');
                            this.classList.remove('btn-outline-danger');
                            this.classList.add('btn-danger');
                        } else {
                            icon.classList.remove('bi-heart-fill', 'text-white');
                            icon.classList.add('bi-heart');
                            this.classList.remove('btn-danger');
                            this.classList.add('btn-outline-danger');
                        }
                    } else {
                        alert(data.message);
                    }
                });
            });
        });
    </script>
</body>
</html>