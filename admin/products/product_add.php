<?php
// admin/products/add_product.php

session_start();
require '../../includes/functions.php';
require '../../includes/db.php';

// Проверка авторизации
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $category_id = trim($_POST['category_id']);
    $image = '';

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
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) { // Максимальный размер: 2 МБ
            $errors['image'] = "Файл слишком большой (максимум 2 МБ).";
        } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $errors['image'] = "Разрешены только JPG, JPEG, PNG и GIF.";
        } else {
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $errors['image'] = "Ошибка загрузки изображения.";
            } else {
                $image = "/uploads/" . basename($_FILES['image']['name']);
            }
        }
    }

    // Добавление товара
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $category_id, $image]);

        redirect('/admin/products/products_list.php');
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавление товара</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Добавление товара</h1>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Название:</label>
                <input type="text" name="name" id="name" class="form-control" required>
                <?php if (!empty($errors['name'])): ?>
                    <div class="text-danger small"><?= htmlspecialchars($errors['name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Описание:</label>
                <textarea name="description" id="description" class="form-control"></textarea>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Цена:</label>
                <input type="number" step="0.01" name="price" id="price" class="form-control" required>
                <?php if (!empty($errors['price'])): ?>
                    <div class="text-danger small"><?= htmlspecialchars($errors['price']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="category_id" class="form-label">Категория:</label>
                <input type="number" name="category_id" id="category_id" class="form-control" required>
                <?php if (!empty($errors['category_id'])): ?>
                    <div class="text-danger small"><?= htmlspecialchars($errors['category_id']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Изображение:</label>
                <input type="file" name="image" id="image" class="form-control">
                <?php if (!empty($errors['image'])): ?>
                    <div class="text-danger small"><?= htmlspecialchars($errors['image']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Добавить</button>
            <a href="/admin/products/products_list.php" class="btn btn-secondary">Отмена</a>
        </form>
    </div>
</body>
</html>