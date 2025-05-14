<?php
session_start();
require '../../includes/db.php';

$category_id = $_GET['id'] ?? null;

if (!$category_id || !is_numeric($category_id)) {
    header('Location: /admin/categories/categories_list.php');
    exit;
}

// Получаем данные о категории
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header('Location: /admin/categories/categories_list.php');
    exit;
}

// Получаем список родительских категорий
$stmt_parents = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL OR id != $category_id ORDER BY name");
$parents = $stmt_parents->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать категорию</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="/admin/categories/categories_list.php">
            <i class="bi bi-pencil-square me-2"></i>Редактировать категорию
        </a>
        <a href="/admin/categories/categories_list.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Назад
        </a>
    </div>
</nav>

<div class="container">
    <h2 class="mb-4">Редактировать категорию #<?= $category['id'] ?></h2>

    <form method="POST" action="/admin/categories/update_category.php">
        <input type="hidden" name="id" value="<?= $category['id'] ?>">

        <!-- Название -->
        <div class="mb-3">
            <label for="name" class="form-label">Название категории</label>
            <input type="text" name="name" class="form-control" id="name" value="<?= htmlspecialchars($category['name']) ?>" required>
        </div>

        <!-- Родительская категория -->
        <div class="mb-3">
            <label for="parent_id" class="form-label">Родительская категория</label>
            <select name="parent_id" id="parent_id" class="form-select">
                <option value="">Без родителя</option>
                <?php foreach ($parents as $parent): ?>
                    <option value="<?= $parent['id'] ?>" <?= $parent['id'] == $category['parent_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($parent['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Slug -->
        <div class="mb-3">
            <label for="slug" class="form-label">Slug (URL)</label>
            <input type="text" name="slug" class="form-control" id="slug" value="<?= htmlspecialchars($category['slug']) ?>">
        </div>

        <!-- Кнопки -->
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Сохранить изменения</button>
            <a href="/admin/categories/categories_list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Назад
            </a>
        </div>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>