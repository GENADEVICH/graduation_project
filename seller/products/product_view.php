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

$product = getProductById($pdo, $product_id);
if (!$product) {
    die("Товар не найден.");
}

if ($product['seller_id'] != $seller_id) {
    die("Вы не можете просматривать чужой товар.");
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Просмотр товара</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="/seller/dashboard.php"><i class="bi bi-shop-window me-2"></i>Панель продавца</a>
        <a href="/pages/profile.php" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Выйти</a>
    </div>
</nav>

<div class="container">
    <h1 class="mb-4">Информация о товаре</h1>

    <div class="card mb-4">
        <div class="row g-0">
            <div class="col-md-4">
                <?php if (!empty($product['main_image'])): ?>
                    <img src="<?= htmlspecialchars($product['main_image']) ?>" class="img-fluid rounded-start" alt="Изображение товара">
                <?php else: ?>
                    <img src="/assets/images/no-image.png" class="img-fluid rounded-start" alt="Нет изображения">
                <?php endif; ?>
            </div>
            <div class="col-md-8">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                    <p class="card-text"><strong>Цена:</strong> <?= number_format($product['price'], 2, ',', ' ') ?> ₽</p>
                    <p class="card-text"><strong>В наличии:</strong> <?= (int)$product['stock'] ?> шт.</p>
                    <p class="card-text"><strong>Описание:</strong><br><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    <a href="product_edit.php?id=<?= $product['id'] ?>" class="btn btn-warning me-2"><i class="bi bi-pencil"></i> Редактировать</a>
                    <a href="product_delete.php?id=<?= $product['id'] ?>" class="btn btn-danger" onclick="return confirm('Вы уверены, что хотите удалить этот товар?');"><i class="bi bi-trash"></i> Удалить</a>
                    <a href="products_list.php" class="btn btn-secondary ms-2">Назад к списку</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
