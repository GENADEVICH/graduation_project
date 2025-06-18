<?php
session_start();
require '../../includes/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header('Location: /pages/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller_id = $stmt->fetchColumn();

$product_id = $_GET['id'] ?? null;

if (!$product_id) {
    die("Некорректный ID товара.");
}

// Получаем товар
$product = getProductById($pdo, $product_id);
if (!$product) {
    include '../includes/header.php';
    echo "<div class='container mt-5'><h2>Товар не найден</h2></div></body></html>";
    exit;
}

// Защита от редактирования чужих товаров
if ($product['seller_id'] != $seller_id) {
    die("Вы не можете редактировать чужой товар.");
}

// Если форма отправлена
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $description = $_POST['description'] ?? '';

    // Валидация данных
    if (empty($name)) {
        die("Название товара обязательно.");
    }
    if ($price <= 0) {
        die("Цена должна быть больше нуля.");
    }

    try {
        // Обновляем основные данные товара
        $stmt = $pdo->prepare("
            UPDATE products 
            SET name = ?, price = ?, stock = ?, description = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $price, $stock, $description, $product_id]);

        // Обработка нового основного изображения
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../assets/images/';
            $uploadFile = $uploadDir . uniqid('img_') . '_' . basename($_FILES['main_image']['name']);

            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $uploadFile)) {
                $stmt = $pdo->prepare("UPDATE products SET main_image = ? WHERE id = ?");
                $stmt->execute([$uploadFile, $product_id]);
            } else {
                echo "Ошибка при загрузке изображения.";
            }
        }

        // Устанавливаем уведомление в сессию
        $_SESSION['success_message'] = "Товар успешно обновлен.";

        // Перенаправление после сохранения
        header("Location: product_edit.php?id=$product_id");
        exit;
    } catch (PDOException $e) {
        die("Ошибка при обновлении товара: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать товар</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"  rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"  rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="/seller/dashboard.php"><i class="bi bi-shop-window me-2"></i>Панель продавца</a>
        <a href="/pages/profile.php" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Выйти</a>
    </div>
</nav>

<div class="container">
    <h1 class="mb-4">Редактировать товар</h1>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Название товара</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>

            <div class="col-md-6">
                <label for="price" class="form-label">Цена</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?= $product['price'] ?>" required>
            </div>

            <div class="col-md-6">
                <label for="stock" class="form-label">Количество на складе</label>
                <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?= $product['stock'] ?>" required>
            </div>

            <div class="col-md-12">
                <label for="description" class="form-label">Описание товара</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <div class="col-md-12">
                <label for="main_image" class="form-label">Изменить основное изображение</label>
                <input type="file" class="form-control" id="main_image" name="main_image" accept="image/*">
                <?php if (!empty($product['main_image'])): ?>
                    <div class="mt-2">
                        <img src="<?= htmlspecialchars($product['main_image']) ?>" alt="Текущее изображение" width="150" class="img-thumbnail">
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-12 mt-4">
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                <a href="products_list.php" class="btn btn-secondary">Назад</a>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> 
</body>
</html>
