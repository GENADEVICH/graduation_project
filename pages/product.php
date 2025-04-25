<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

$product_id = $_GET['id'] ?? null;

if ($product_id) {
    // Получаем основную информацию о товаре
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Получаем название бренда
    $brand_name = 'Бренд не указан';
    if (!empty($product['brand_id'])) {
        $stmt = $pdo->prepare("SELECT name FROM brands WHERE id = ?");
        $stmt->execute([$product['brand_id']]);
        $brand = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($brand) {
            $brand_name = $brand['name'];
        }
    }

    // Получаем характеристики товара
    $stmt = $pdo->prepare("SELECT attribute_name, attribute_value FROM product_attributes WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $attributes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Возвращает массив [attribute_name => attribute_value]

    // Получаем отзывы покупателей
    $stmt = $pdo->prepare("
        SELECT r.rating, r.review, u.username 
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Вычисляем среднюю оценку и количество отзывов
    $stmt = $pdo->prepare("
        SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews
        FROM reviews
        WHERE product_id = ?
    ");
    $stmt->execute([$product_id]);
    $review_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $avg_rating = round($review_stats['avg_rating'] ?? 0, 1); // Округляем до одного знака после запятой
    $total_reviews = $review_stats['total_reviews'] ?? 0;

    // Получаем дополнительные изображения товара
    $stmt = $pdo->prepare("SELECT image FROM product_images WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
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

    <!-- Bootstrap Icons -->
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
                <div class="d-flex flex-column align-items-center">
                    <!-- Галерея изображений -->
                    <div class="image-gallery">
                        <?php if (!empty($product['main_image'])): ?>
                            <img src="<?= htmlspecialchars($product['main_image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="img-fluid rounded main-image">
                        <?php else: ?>
                            <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="img-fluid rounded main-image">
                        <?php endif; ?>
                    </div>
                    <!-- Миниатюры изображений -->
                    <div class="mt-3 d-flex justify-content-center">
                        <?php if (!empty($images)): ?>
                            <?php foreach ($images as $image): ?>
                                <div class="thumbnail mx-1">
                                    <img src="<?= htmlspecialchars($image) ?>" alt="Миниатюра" class="img-fluid rounded">
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="thumbnail mx-1">
                                    <img src="/assets/images/no-image.jpg" alt="Миниатюра" class="img-fluid rounded">
                                </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Правая колонка: Информация о товаре -->
            <div class="col-md-6">
                <h1 class="mb-3"><?= htmlspecialchars($product['name']) ?></h1>

                <!-- Скидка (если есть) -->
                <?php if (!empty($product['discount'])): ?>
                    <span class="badge bg-danger text-white">-<?= htmlspecialchars($product['discount']) ?>%</span>
                <?php endif; ?>

                <!-- Оценка и отзывы -->
                <div class="mb-2">
                    <span class="text-warning">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star-fill <?= $i <= $avg_rating ? 'text-warning' : 'text-muted' ?>"></i>
                        <?php endfor; ?>
                        <?= htmlspecialchars($avg_rating) ?> •
                    </span>
                    <span class="text-muted"><?= htmlspecialchars($total_reviews) ?> отзывов</span>
                </div>

                <!-- Бренд и статус товара -->
                <div class="mb-2">
                    <span class="badge bg-primary text-white"><?= htmlspecialchars($brand_name) ?></span>
                    <span class="text-success"><i class="bi bi-check-circle-fill"></i> Оригинальный товар</span>
                </div>

                <!-- Состояние товара -->
                <div class="mb-3">
                    <span class="fw-bold">Состояние товара:</span>
                    <span class="badge bg-primary text-white">Новые</span>
                    <span class="badge bg-secondary text-white">Уцененные</span>
                </div>

                <!-- Цена -->
                <div class="mb-3">
                    <h2 class="text-primary fw-bold"><?= htmlspecialchars($product['price']) ?> руб.</h2>
                    <?php if (!empty($product['old_price'])): ?>
                        <del class="text-muted fs-5"><?= htmlspecialchars($product['old_price']) ?> руб.</del>
                    <?php endif; ?>
                </div>

                <!-- Кнопка "Добавить в корзину" -->
                <a href="/pages/cart.php?action=add&id=<?= $product['id'] ?>" class="btn btn-primary btn-lg w-100 mb-2">
                    <i class="bi bi-cart-plus"></i> Добавить в корзину
                </a>

                <!-- Характеристики товара -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>О товаре</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <?php foreach ($attributes as $name => $value): ?>
                                <dt class="col-sm-4"><?= htmlspecialchars($name) ?></dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($value) ?></dd>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                </div>

                <!-- Отзывы покупателей -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Отзывы покупателей</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="mb-3">
                                    <p><strong><?= htmlspecialchars($review['username']) ?></strong></p>
                                    <p>
                                        <?php for ($i = 1; $i <= $review['rating']; $i++): ?>
                                            <i class="bi bi-star-fill text-warning"></i>
                                        <?php endfor; ?>
                                    </p>
                                    <p><?= htmlspecialchars($review['review']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Отзывов пока нет.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS и зависимости -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>