<?php
// admin/products/edit_product.php

session_start();
require '../../includes/functions.php';
require '../../includes/db.php';

// Проверка авторизации
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

// Получение ID товара из URL
$product_id = $_GET['id'] ?? null;

if (!$product_id) {
    redirect('/admin/products/products_list.php');
}

// Получение данных товара
$stmt = $pdo->prepare("SELECT id, name, description, price, category_id, image_url FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    redirect('/admin/products/products_list.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $category_id = trim($_POST['category_id']);
    $image_url = $product['image_url']; // Сохраняем текущее изображение

    // Валидация
    if (empty($name)) {
        $errors['name'] = "Название обязательно.";
    }

    if (empty($price) || !is_numeric($price) || $price <= 0) {
        $errors['price'] = "Цена должна быть числом больше 0.";
    }

    if (empty($category_id) || !is_numeric($category_id)) {
        $errors['category_id'] = "Категория обязательна.";
    }

    // Обработка изображения
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../../uploads/";
        $target_file = $target_dir . basename($_FILES['image']['name']);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Проверка типа файла
        if ($_FILES['image']['size'] > 2 * 1024 * 1280) { // Максимальный размер: 2 МБ
            $errors['image'] = "Файл слишком большой (максимум 2 МБ).";
        } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $errors['image'] = "Разрешены только JPG, JPEG, PNG и GIF.";
        } else {
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $errors['image'] = "Ошибка загрузки изображения.";
            } else {
                $image_url = "/uploads/" . basename($_FILES['image']['name']);
            }
        }
    }

    // Обновление данных
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, image_url = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $category_id, $image_url, $product_id]);

        redirect('/admin/products/products_list.php');
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование товара</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Редактирование товара</h1>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Название:</label>
                <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                <?php if (!empty($errors['name'])): ?>
                    <div class="text-danger small"><?= htmlspecialchars($errors['name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Описание:</label>
                <textarea name="description" id="description" class="form-control"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Цена:</label>
                <input type="number" step="0.01" name="price" id="price" class="form-control" value="<?= htmlspecialchars($product['price']) ?>" required>
                <?php if (!empty($errors['price'])): ?>
                    <div class="text-danger small"><?= htmlspecialchars($errors['price']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="category_id" class="form-label">Категория:</label>
                <input type="number" name="category_id" id="category_id" class="form-control" value="<?= htmlspecialchars($product['category_id']) ?>" required>
                <?php if (!empty($errors['category_id'])): ?>
                    <div class="text-danger small"><?= htmlspecialchars($errors['category_id']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Текущее изображение:</label>
                <br>
                <?php if (!empty($product['image_url'])): ?>
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="Текущее изображение" width="100">
                <?php else: ?>
                    <span>Нет изображения</span>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="new_image" class="form-label">Новое изображение:</label>
                <input type="file" name="image" id="new_image" class="form-control">
                <?php if (!empty($errors['image'])): ?>
                    <div class="text-danger small"><?= htmlspecialchars($errors['image']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Сохранить</button>
            <a href="/admin/products/products_list.php" class="btn btn-secondary">Отмена</a>
        </form>
    </div>
</body>
</html>