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


// Пагинация
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $per_page;

// Поиск
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Подготавливаем запрос с учётом поиска
    $query = "SELECT id, name, price, stock, created_at, main_image FROM products WHERE seller_id = ?";
    $params = [$seller_id];

    if (!empty($search)) {
        $query .= " AND name LIKE ?";
        $params[] = "%$search%";
    }

    $query .= " ORDER BY created_at DESC LIMIT $start, $per_page";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем общее количество товаров
    $countQuery = "SELECT COUNT(*) FROM products WHERE seller_id = ?";
    $countParams = [$seller_id];

    if (!empty($search)) {
        $countQuery .= " AND name LIKE ?";
        $countParams[] = "%$search%";
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($countParams);
    $total_products = $countStmt->fetchColumn();

    $total_pages = ceil($total_products / $per_page);
} catch (PDOException $e) {
    die("Ошибка при получении товаров: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои товары</title>
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
    <h1 class="mb-4">Мои товары</h1>

    <!-- Форма поиска -->
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-6">
            <input type="text" name="search" class="form-control" placeholder="Поиск по названию..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Найти</button>
        </div>
        <div class="col-md-4 text-end">
            <a href="/seller/products/product_add.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Добавить товар</a>
        </div>
    </form>

    <?php if (empty($products)): ?>
        <p class="text-muted">У вас пока нет товаров.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Изображение</th>
                        <th>Название</th>
                        <th>Цена</th>
                        <th>В наличии</th>
                        <th>Дата добавления</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php
                            $image = !empty($product['main_image'])
                                ? $product['main_image']
                                : '/assets/images/no-image.jpg';
                        ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($image) ?>" width="60" height="60" class="rounded object-fit-cover">
                            </td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= number_format($product['price'], 2, ',', ' ') ?> ₽</td>
                            <td><?= $product['stock'] > 0 ? '<span class="badge bg-success">В наличии</span>' : '<span class="badge bg-danger">Нет в наличии</span>' ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($product['created_at'])) ?></td>
                            <td>
                                <a href="/seller/products/product_view.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary" title="Просмотреть"><i class="bi bi-eye"></i></a>
                                <a href="/seller/products/product_edit.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-warning" title="Редактировать"><i class="bi bi-pencil"></i></a>
                                <a href="/seller/products/product_delete.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-danger" title="Удалить" onclick="return confirm('Вы уверены?');"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Пагинация -->
        <nav aria-label="Страницы товаров">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Поведение формы поиска -->
<script>
document.querySelector('form').addEventListener('submit', function(e) {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput.value.trim() === '') {
        e.preventDefault();
        window.location.href = '?page=1';
    }
});
</script>

</body>
</html>
