<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

// Инициализация списка желаний, если он еще не существует
if (!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

// Обработка действий со списком желаний
$action = $_GET['action'] ?? null;
$product_id = $_GET['id'] ?? null;

if ($action === 'add' && $product_id) {
    // Добавление товара в список желаний
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product && !isset($_SESSION['wishlist'][$product_id])) {
        // Если товара нет в списке желаний, добавляем его
        $_SESSION['wishlist'][$product_id] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image']
        ];
    }
    redirect('/pages/wishlist.php');
} elseif ($action === 'remove' && $product_id) {
    // Удаление товара из списка желаний
    if (isset($_SESSION['wishlist'][$product_id])) {
        unset($_SESSION['wishlist'][$product_id]);
    }
    redirect('/pages/wishlist.php');
} elseif ($action === 'clear') {
    // Очистка списка желаний
    $_SESSION['wishlist'] = [];
    redirect('/pages/wishlist.php');
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список желаний</title>

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
    <div style="height: 1220px">
        <h1 class="text-center mb-4">Список желаний</h1>

        <?php if (empty($_SESSION['wishlist'])): ?>
            <div class="alert alert-info text-center">
                Ваш список желаний пуст.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach ($_SESSION['wishlist'] as $item): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="card-img-top rounded" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="card-img-top rounded" style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
                                <p class="card-text flex-grow-1"><?= htmlspecialchars($item['description'] ?? 'Описание отсутствует.') ?></p>
                                <p class="card-text"><strong>Цена:</strong> <?= htmlspecialchars($item['price']) ?> руб.</p>
                                <div class="d-flex gap-2 mt-auto">
                                    <a href="/pages/cart.php?action=add&id=<?= $item['id'] ?>" class="btn btn-primary flex-grow-1">
                                        <i class="bi bi-cart-plus"></i> В корзину
                                    </a>
                                    <a href="/pages/wishlist.php?action=remove&id=<?= $item['id'] ?>" class="btn btn-danger flex-grow-1">
                                        <i class="bi bi-trash"></i> Удалить
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-end mt-4">
                <a href="/pages/wishlist.php?action=clear" class="btn btn-warning">
                    <i class="bi bi-trash"></i> Очистить список желаний
                </a>
            </div>
        <?php endif; ?>
        </div>
    </main>


    <!-- Bootstrap JS и зависимости -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>