<?php
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}

$errors = [];
$success = '';

// Получаем список родительских категорий
$stmt_parents = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name");
$parents = $stmt_parents->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $slug = trim($_POST['slug'] ?? '');

    // Валидация
    if (empty($name)) {
        $errors[] = 'Название категории не может быть пустым';
    }

    // Если slug задан — проверяем уникальность
    if (!empty($slug)) {
        $stmt_check = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt_check->execute([$slug]);
        if ($stmt_check->fetch()) {
            $errors[] = 'Этот slug уже используется';
        }
    }

    // Если нет ошибок — добавляем
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, parent_id, slug) VALUES (?, ?, ?)");
            $stmt->execute([$name, $parent_id, $slug ?: null]);

            header('Location: categories_list.php?status=added');
            exit;
        } catch (PDOException $e) {
            die("Ошибка при добавлении категории: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить категорию</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="/admin/categories/categories_list.php">
            <i class="bi bi-plus-circle me-2"></i>Добавить категорию
        </a>
        <a href="/admin/categories/categories_list.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Назад
        </a>
    </div>
</nav>

<div class="container">
    <h2 class="mb-4">Добавить новую категорию</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" class="needs-validation" novalidate>
        <!-- Название -->
        <div class="mb-3">
            <label for="name" class="form-label">Название категории</label>
            <input type="text" name="name" class="form-control" id="name" required>
            <div class="invalid-feedback">Введите название категории</div>
        </div>

        <!-- Родительская категория -->
        <div class="mb-3">
            <label for="parent_id" class="form-label">Родительская категория (необязательно)</label>
            <select name="parent_id" id="parent_id" class="form-select">
                <option value="">Без родителя</option>
                <?php foreach ($parents as $parent): ?>
                    <option value="<?= $parent['id'] ?>"><?= htmlspecialchars($parent['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Slug -->
        <div class="mb-3">
            <label for="slug" class="form-label">Slug (URL-путь, опционально)</label>
            <input type="text" name="slug" id="slug" class="form-control" placeholder="electronics/laptops">
        </div>

        <!-- Кнопки -->
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Сохранить</button>
            <a href="/admin/categories/categories_list.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Отмена</a>
        </div>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Валидация формы -->
<script>
(() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
</body>
</html>