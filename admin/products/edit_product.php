<?php
session_start();
require '../../includes/db.php';

$product_id = $_GET['id'] ?? null;

if (!$product_id || !is_numeric($product_id)) {
    header('Location: products_list.php');
    exit;
}

// Получаем данные о товаре
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products_list.php');
    exit;
}

// Получаем список категорий
$stmt_cats = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать товар</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="/admin/products/products_list.php">
            <i class="bi bi-pencil-square me-2"></i>Редактировать товар
        </a>
        <a href="/admin/products/products_list.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Назад
        </a>
    </div>
</nav>

<div class="container">
    <h2 class="mb-4">Редактировать товар #<?= $product['id'] ?></h2>

    <form method="POST" action="/admin/products/update_product.php" class="needs-validation" novalidate>
        <input type="hidden" name="id" value="<?= $product['id'] ?>">

        <!-- Название -->
        <div class="mb-3">
            <label for="name" class="form-label">Название товара</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
        </div>

        <!-- Описание -->
        <div class="mb-3">
            <label for="description" class="form-label">Описание</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
        </div>

        <!-- Цена -->
        <div class="mb-3">
            <label for="price" class="form-label">Цена</label>
            <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?= htmlspecialchars($product['price']) ?>" required>
        </div>

        <!-- Количество -->
        <div class="mb-3">
            <label for="quantity" class="form-label">Количество на складе</label>
            <input type="number" min="0" class="form-control" id="quantity" name="quantity" value="<?= htmlspecialchars($product['quantity']) ?>" required>
        </div>

        <!-- Категория -->
        <div class="mb-3">
            <label for="category_id" class="form-label">Категория</label>
            <select class="form-select" id="category_id" name="category_id">
                <option value="">Без категории</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Изображение -->
        <div class="mb-3">
            <label for="image_url" class="form-label">Ссылка на изображение</label>
            <input type="text" class="form-control" id="image_url" name="image_url" value="<?= htmlspecialchars($product['image_url']) ?>">
        </div>

        <!-- Кнопки -->
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Сохранить изменения</button>
            <a href="/admin/products/products_list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Назад
            </a>
        </div>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap @5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Валидация формы -->
<script>
    (() => {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>
</body>
</html>