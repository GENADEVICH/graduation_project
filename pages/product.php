<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

$product_id = $_GET['id'] ?? null;

$user_id = $_SESSION['user_id'] ?? null;
$wishlistItems = [];

if ($user_id) {
    $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $wishlistItems = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

if ($product_id) {
    // Получаем основную информацию о товаре
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Товар не найден.");
    }

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

    // Получаем характеристики из новых таблиц
  $stmt = $pdo->prepare("
    SELECT 
        c.name AS attribute_name, 
        c.value_type,
        pc.value_string,
        pc.value_integer,
        pc.value_decimal,
        pc.value_boolean
    FROM product_characteristics pc
    JOIN characteristics c ON pc.characteristic_id = c.id
    WHERE pc.product_id = ?
");
$stmt->execute([$product_id]);
$rawAttributes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$attributes = [];
foreach ($rawAttributes as $row) {
    switch ($row['value_type']) {
        case 'string':
            $value = $row['value_string'];
            break;
        case 'integer':
            $value = $row['value_integer'];
            break;
        case 'decimal':
            $value = $row['value_decimal'];
            break;
        case 'boolean':
            $value = $row['value_boolean'] ? 'Да' : 'Нет';
            break;
        default:
            $value = null;
    }

    if ($value !== null && $value !== '') {
        $attributes[$row['attribute_name']] = $value;
    }
}


    // Получаем отзывы покупателей
    $stmt = $pdo->prepare("
        SELECT r.rating, r.comment, u.username 
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ?
        ORDER BY r.created_at DESC
        LIMIT 10
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

    $avg_rating = round($review_stats['avg_rating'] ?? 0, 1);
    $total_reviews = $review_stats['total_reviews'] ?? 0;

    // Получаем дополнительные изображения товара
    $stmt = $pdo->prepare("SELECT image FROM product_images WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    die("Некорректный ID товара.");
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<main class="container mt-4 mb-5">
    <div class="row g-4">
        <!-- Галерея изображений -->
        <div class="col-md-6">
            <div class="image-gallery mb-3 border rounded overflow-hidden shadow-sm position-relative" style="height: 450px;">
                <img src="<?= htmlspecialchars($product['main_image'] ?? '/assets/images/no-image.jpg') ?>" 
                    alt="<?= htmlspecialchars($product['name']) ?>" 
                    class="w-100 h-100 object-fit-contain bg-light" id="mainImage">
            </div>
            
            <div class="d-flex justify-content-center gap-2 flex-wrap mt-2">
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $image): ?>
                        <img src="<?= htmlspecialchars($image) ?>" 
                            alt="Миниатюра" 
                            class="img-thumbnail thumb" 
                            style="cursor:pointer; width: 80px; height: 80px; object-fit: contain;"
                            onclick="changeMainImage(this.src)">
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <img src="/assets/images/no-image.jpg" 
                            alt="Миниатюра" 
                            class="img-thumbnail thumb" 
                            style="cursor:pointer; width: 80px; height: 80px; object-fit: contain;">
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
    </div>
        <!-- Описание товара -->
        <div class="col-md-6">
            <h1 class="mb-3"><?= htmlspecialchars($product['name']) ?></h1>

            <?php if (!empty($product['discount'])): ?>
                <span class="badge bg-danger text-white mb-2">-<?= htmlspecialchars($product['discount']) ?>%</span>
            <?php endif; ?>

            <div class="mb-3">
                <span class="text-warning fs-5">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi bi-star-fill <?= $i <= $avg_rating ? 'text-warning' : 'text-muted' ?>"></i>
                    <?php endfor; ?>
                </span>
                <span class="ms-2"><?= htmlspecialchars($avg_rating) ?> (<?= htmlspecialchars($total_reviews) ?>)</span>
            </div>

            <div class="mb-3">
                <span class="badge bg-primary"><?= htmlspecialchars($brand_name) ?></span>
                <span class="text-success ms-2"><i class="bi bi-check-circle-fill"></i> Оригинальный товар</span>
            </div>

            <div class="mb-3">
                <h2 class="text-primary fw-bold"><?= htmlspecialchars($product['price']) ?> ₽</h2>
                <?php if (!empty($product['old_price'])): ?>
                    <del class="text-muted"><?= htmlspecialchars($product['old_price']) ?> ₽</del>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-3 mb-4">
                <button class="btn btn-primary btn-lg flex-grow-1 add-to-cart" data-product-id="<?= $product['id'] ?>">
                    <i class="bi bi-cart-plus me-2"></i> В корзину
                </button>
                <?php
                    $isInWishlist = in_array($product['id'], $wishlistItems);
                ?>
                <button class="btn btn-lg px-3 add-to-wishlist <?= $isInWishlist ? 'btn-danger' : 'btn-outline-danger' ?>" data-product-id="<?= $product['id'] ?>">
                    <i class="bi <?= $isInWishlist ? 'bi-heart-fill text-white' : 'bi-heart' ?>"></i>
                </button>
                <button class="btn btn-outline-secondary btn-lg px-3" onclick="shareProduct(event)">
                    <i class="bi bi-share me-2"></i>
                </button>
            </div>

            <!-- Характеристики -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Характеристики</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($attributes)): ?>
                        <dl class="row g-2">
                            <?php foreach ($attributes as $name => $value): ?>
                                <dt class="col-sm-4 fw-semibold"><?= htmlspecialchars($name) ?></dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($value) ?></dd>
                            <?php endforeach; ?>
                        </dl>
                    <?php else: ?>
                        <p class="text-muted">Характеристики не указаны.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Отзывы -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Отзывы покупателей</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="card mb-3 border-0 bg-light">
                                <div class="card-body p-3">
                                    <h6 class="mb-1"><?= htmlspecialchars($review['username']) ?></h6>
                                    <p class="text-warning mb-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star-fill <?= $i <= $review['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                        <?php endfor; ?>
                                    </p>
                                    <p class="mb-0"><?= htmlspecialchars($review['comment']) ?></p>
                                </div>
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

<script>
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function () {
        const productId = this.dataset.productId;
        const originalText = this.innerHTML;

        // Отправляем AJAX-запрос
        fetch('../api/add_to_cart_ajax.php?id=' + productId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.innerHTML = '<i class="bi bi-check-circle me-2"></i>Добавлено';
                    this.disabled = true;

                    // Через 3 секунды возвращаем исходный вид
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 3000);

                    // Опционально: обновить количество в корзине в интерфейсе
                    updateCartCount(1);
                } else {
                    alert(data.message || 'Ошибка при добавлении товара');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при добавлении товара в корзину.');
            });
    });
});
</script>

<script>
document.querySelectorAll('.add-to-wishlist').forEach(button => {
    button.addEventListener('click', function (e) {
        e.preventDefault();
        const productId = this.dataset.productId;
        const icon = this.querySelector('i');
        const isAdding = icon.classList.contains('bi-heart'); // если пустое сердце — значит добавляем

        fetch('/api/add_to_wishlist_ajax.php?id=' + productId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ remove: !isAdding })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (isAdding) {
                    icon.classList.remove('bi-heart');
                    icon.classList.add('bi-heart-fill', 'text-white');
                    this.classList.remove('btn-outline-danger');
                    this.classList.add('btn-danger');
                    updateWishlistCount(1);
                } else {
                    icon.classList.remove('bi-heart-fill', 'text-white');
                    icon.classList.add('bi-heart');
                    this.classList.remove('btn-danger');
                    this.classList.add('btn-outline-danger');
                    updateWishlistCount(-1);
                }
            } else {
                alert(data.message || 'Ошибка при изменении избранного');
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            alert('Произошла ошибка при изменении избранного.');
        });
    });
});





function updateWishlistCount(delta) {
    const badge = document.querySelector('a[href="/pages/wishlist.php"] .badge');
    if (badge) {
        let current = parseInt(badge.textContent) || 0;
        badge.textContent = current + delta;
    }
}
</script>
<script>
    function changeMainImage(src) {
        document.getElementById('mainImage').src = src;
    }

    function updateCartCount(delta) {
    const cartBadge = document.querySelector('a[href="/pages/cart.php"] .badge');
    if (cartBadge) {
        let current = parseInt(cartBadge.textContent) || 0;
        cartBadge.textContent = current + delta;
    }
}
</script>

<script>
function shareProduct(event) {
    event.preventDefault();

    const productId = <?= $product['id'] ?>;
    const productUrl = window.location.origin + '/pages/product.php?id=' + productId;

    // Пытаемся скопировать через Clipboard API
    navigator.clipboard.writeText(productUrl)
        .then(() => {
            showTooltip('Ссылка скопирована!', 'success');
        })
        .catch(() => {
            // Альтернативный способ (для мобильных или старых браузеров)
            const textArea = document.createElement('textarea');
            textArea.value = productUrl;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showTooltip('Ссылка скопирована (через буфер)', 'warning');
        });
}

// Всплывающее уведомление
function showTooltip(message, type = 'info') {
    let tooltip = document.getElementById('custom-tooltip');

    if (!tooltip) {
        tooltip = document.createElement('div');
        tooltip.id = 'custom-tooltip';
        tooltip.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            z-index: 10000;
            font-weight: bold;
            font-size: 1rem;
            transition: opacity 0.3s ease;
        `;
        document.body.appendChild(tooltip);
    }

    tooltip.textContent = message;
    tooltip.style.backgroundColor =
        type === 'success' ? '#28a745' :
        type === 'warning' ? '#ffc107' :
        '#007bff';

    tooltip.style.opacity = '1';

    setTimeout(() => {
        tooltip.style.opacity = '0';
    }, 2500);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>