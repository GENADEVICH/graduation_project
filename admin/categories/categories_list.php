<?php
session_start();
require '../../includes/db.php';

$search = $_GET['search'] ?? '';

try {
    if ($search) {
        $stmt = $pdo->prepare("SELECT c.id, c.name, c.slug, p.name AS parent_name 
                                FROM categories c
                                LEFT JOIN categories p ON c.parent_id = p.id
                                WHERE c.name LIKE ? OR c.slug LIKE ?
                                ORDER BY c.name");
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt = $pdo->query("SELECT c.id, c.name, c.slug, p.name AS parent_name 
                              FROM categories c
                              LEFT JOIN categories p ON c.parent_id = p.id
                              ORDER BY c.name");
    }

    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при загрузке категорий: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список категорий</title>
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
            <i class="bi bi-tags me-2"></i>Категории
        </a>
        <a href="/admin/logout.php" class="btn btn-outline-danger">
            <i class="bi bi-box-arrow-right"></i> Выйти
        </a>
    </div>
</nav>

<div class="container">
    <h2 class="mb-4">Список категорий</h2>

    <!-- Форма поиска -->
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Поиск по названию или slug..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-outline-primary" type="submit">
                <i class="bi bi-search"></i> Поиск
            </button>
            <?php if ($search): ?>
                <a href="/admin/categories/categories_list.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Очистить
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Таблица категорий -->
    <?php if (empty($categories)): ?>
        <div class="alert alert-info">Нет категорий<?= $search ? ' по вашему запросу' : '' ?>.</div>
    <?php else: ?>
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Родительская категория</th>
                    <th>Slug</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= htmlspecialchars($cat['id']) ?></td>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td><?= htmlspecialchars($cat['parent_name'] ?? 'Нет') ?></td>
                        <td><?= htmlspecialchars($cat['slug'] ?? '-') ?></td>
                        <td>
                            <a href="/admin/categories/edit_category.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-primary me-2">
                                <i class="bi bi-pencil"></i> Редактировать
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $cat['id'] ?>">
                                <i class="bi bi-trash"></i> Удалить
                            </a>

                            <!-- Modal для удаления -->
                            <div class="modal fade" id="deleteModal<?= $cat['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel">Подтверждение удаления</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                                        </div>
                                        <div class="modal-body">
                                            Вы действительно хотите удалить категорию "<strong><?= htmlspecialchars($cat['name']) ?></strong>"?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                            <a href="/admin/categories/delete_category.php?id=<?= $cat['id'] ?>" class="btn btn-danger">Удалить</a>
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

    <!-- Кнопка добавления категории -->
    <div class="mt-3">
        <a href="/admin/categories/add_category.php" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> Добавить категорию
        </a>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>