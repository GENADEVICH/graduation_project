<?php
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header('Location: /pages/login.php');
    exit;
}
$seller_id = $_SESSION['user_id'];


// Получаем список товаров продавца
try {
    $stmt = $pdo->prepare("SELECT id, name, price, stock, created_at FROM products WHERE seller_id = ? ORDER BY created_at DESC");
    $stmt->execute([$seller_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при получении товаров: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Мои товары</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>

<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="/seller/dashboard.php"><i class="bi bi-shop-window me-2"></i>Панель продавца</a>
        <a href="/pages/profile.php" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Выйти</a>
    </div>
</nav>

<div class="container">
    <h1 class="mb-4">Мои товары</h1>
    <a href="/seller/products/product_add.php" class="btn btn-success mb-3">Добавить новый товар</a>

    <?php if (empty($products)): ?>
        <p>У вас пока нет товаров.</p>
    <?php else: ?>
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Цена</th>
                    <th>В наличии</th>
                    <th>Дата добавления</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['id']) ?></td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= number_format($product['price'], 2, ',', ' ') ?> ₽</td>
                        <td><?= (int)$product['stock'] ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($product['created_at'])) ?></td>
                        <td>
                            <a href="/seller/products/product_view.php?id=<?= $product['id'] ?>" class="btn btn-primary btn-sm">Просмотр</a>
                            <a href="/seller/products/product_edit.php?id=<?= $product['id'] ?>" class="btn btn-warning btn-sm">Редактировать</a>
                            <a href="/seller/products/product_delete.php?id=<?= $product['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить этот товар?');">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
