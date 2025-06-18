<?php
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}

$search = $_GET['search'] ?? '';

try {
    if ($search) {
        $stmt = $pdo->prepare("SELECT p.id, p.name, p.price, c.name AS category_name 
                                FROM products p
                                LEFT JOIN categories c ON p.category_id = c.id
                                WHERE p.name LIKE ? OR c.name LIKE ?");
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt = $pdo->query("SELECT p.id, p.name, p.price, c.name AS category_name 
                              FROM products p
                              LEFT JOIN categories c ON p.category_id = c.id");
    }
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при загрузке товаров: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список товаров</title>
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
        <a class="navbar-brand fs-4" href="/admin/dashboard.php">
            <i class="bi bi-cart me-2"></i>Товары
        </a>
        <a href="/pages/profile.php" class="btn btn-outline-danger">
            <i class="bi bi-box-arrow-right"></i> Выйти
        </a>
    </div>
</nav>

<div class="container">
    <h2 class="mb-4">Список товаров</h2>

    <!-- Форма поиска -->
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Поиск по названию или категории..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-outline-primary" type="submit">
                <i class="bi bi-search"></i> Поиск
            </button>
            <?php if ($search): ?>
                <a href="/admin/products/products_list.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Очистить
                </a>
            <?php endif; ?>
        </div>
    </form>
    <div class="mt-3">
                <a href="/admin/products/product_add.php" class="btn btn-success">
                    <i class="bi bi-plus-lg"></i> Добавить товар
                </a>
            </div>

    <?php if (empty($products)): ?>
        <div class="alert alert-info">Нет товаров<?= $search ? ' по вашему запросу' : '' ?>.</div>
    <?php else: ?>
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Цена</th>
                    <!-- <th>Количество</th> -->
                    <th>Категория</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['id']) ?></td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= number_format($product['price'], 2, ',', ' ') ?> ₽</td>
                        <!-- <td><?= htmlspecialchars($product['quantity']) ?></td> -->
                        <td><?= htmlspecialchars($product['category_name'] ?? 'Без категории') ?></td>
                        <td>
                            <a href="/admin/products/edit_product.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary me-2">
                                <i class="bi bi-pencil"></i> Редактировать
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $product['id'] ?>">
                                <i class="bi bi-trash"></i> Удалить
                            </a>

                            <!-- Modal для удаления -->
                            <div class="modal fade" id="deleteModal<?= $product['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel">Подтверждение удаления</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                                        </div>
                                        <div class="modal-body">
                                            Вы действительно хотите удалить товар "<strong><?= htmlspecialchars($product['name']) ?></strong>"?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                            <a href="/admin/products/delete_product.php?id=<?= $product['id'] ?>" class="btn btn-danger">Удалить</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
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