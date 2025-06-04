<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

// Проверка авторизации
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user_id = $_SESSION['user_id'];

// Обработка действий со списком желаний
$action = $_GET['action'] ?? null;
$product_id = $_GET['id'] ?? null;

if ($action === 'add' && $product_id) {
    // Добавление товара в список желаний
    $stmt = $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $product_id]);
    redirect('/pages/wishlist.php');
} elseif ($action === 'remove' && $product_id) {
    // Удаление товара из списка желаний
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    redirect('/pages/wishlist.php');
} elseif ($action === 'clear') {
    // Очистка списка желаний
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    redirect('/pages/wishlist.php');
}

// Получение товаров из списка желаний
$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.price, p.main_image, p.description
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ?
");
$stmt->execute([$user_id]);
$wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
<body class="bg-light">
    <?php include '../includes/header.php'; ?>

    <main class="container py-5">
        <h1 class="text-center mb-5">Ваш список желаний</h1>

        <?php if (empty($wishlistItems)): ?>
            <div class="alert alert-info text-center shadow-sm py-4">
                <i class="bi bi-heart-dash display-4"></i>
                <p class="mt-3 mb-0">Ваш список желаний пуст.</p>
                <a href="/pages/home.php" class="btn btn-primary mt-3"><i class="bi bi-arrow-left-circle"></i> Вернуться к покупкам</a>
            </div>
        <?php else: ?>
            <div class="row g-4 justify-content-center">
                <?php foreach ($wishlistItems as $item): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                            <img src="<?= htmlspecialchars($item['main_image'] ?? '/assets/images/no-image.jpg') ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                 class="card-img-top" 
                                 style="height: 220px; object-fit: contain; background-color: #f8f9fa;">
                            <div class="card-body d-flex flex-column p-3">
                                <h5 class="card-title fs-6 fw-bold"><?= htmlspecialchars($item['name']) ?></h5>
                                <p class="card-text text-muted small flex-grow-1">
                                    <?= htmlspecialchars($item['description'] ?? 'Описание отсутствует.') ?>
                                </p>
                                <p class="card-text text-primary fw-semibold">
                                    <?= htmlspecialchars($item['price']) ?> ₽
                                </p>
                                <div class="d-flex gap-2 mt-auto">
                                    <a href="/pages/cart.php?action=add&id=<?= $item['id'] ?>" 
                                       class="btn btn-outline-primary btn-sm flex-fill">
                                        <i class="bi bi-cart-plus me-1"></i> В корзину
                                    </a>
                                    <a href="/pages/wishlist.php?action=remove&id=<?= $item['id'] ?>" 
                                       class="btn btn-outline-danger btn-sm flex-fill">
                                        <i class="bi bi-trash me-1"></i> Удалить
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-end mt-4">
                <a href="/pages/wishlist.php?action=clear" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-trash me-1"></i> Очистить список желаний
                </a>
            </div>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>