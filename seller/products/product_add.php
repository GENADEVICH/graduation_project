<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/../../includes/db.php';


if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header('Location: /pages/login.php');
    exit;
}
$seller_id = $_SESSION['user_id'];

$errors = [];
$name = '';
$description = '';
$price = '';
$category_id = '';
$brand_id = '';
$stock = 0;
$slug = '';
$main_image = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $brand_id = $_POST['brand_id'] ?? null;
    $stock = $_POST['stock'] ?? 0;
    $slug = trim($_POST['slug'] ?? '');

    // Валидация
    if ($name === '') {
        $errors['name'] = 'Название товара обязательно.';
    }
    if ($price === '' || !is_numeric($price) || $price < 0) {
        $errors['price'] = 'Введите корректную цену.';
    }
    if ($category_id === '' || !is_numeric($category_id)) {
        $errors['category_id'] = 'Выберите категорию.';
    }
    if (!is_numeric($stock) || $stock < 0) {
        $errors['stock'] = 'Количество на складе должно быть неотрицательным числом.';
    }
    if ($slug === '') {
        // Генерируем slug из названия, если не задан
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }

    // Проверка уникальности slug
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() > 0) {
        $errors['slug'] = 'Такой URL (slug) уже используется. Придумайте другой.';
    }

    // Обработка загрузки изображения (опционально)
    if (!empty($_FILES['main_image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['main_image']['type'], $allowed_types)) {
            $errors['main_image'] = 'Допустимы только изображения JPEG, PNG и GIF.';
        } elseif ($_FILES['main_image']['error'] !== 0) {
            $errors['main_image'] = 'Ошибка при загрузке изображения.';
        } else {
            // Сохраняем файл
            $ext = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $destination = __DIR__ . '/../../uploads/products' . $filename;

            if (!move_uploaded_file($_FILES['main_image']['tmp_name'], $destination)) {
                $errors['main_image'] = 'Не удалось сохранить изображение.';
            } else {
                $main_image = $filename;
            }
        }
    }

    if (empty($errors)) {
        // Вставляем товар в БД
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, brand_id, slug, main_image, seller_id, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name,
            $description,
            $price,
            $category_id,
            $brand_id ?: null,
            $slug,
            $main_image,
            $user['id'],
            $stock
        ]);

        // После успешного добавления можно редиректить или показать сообщение
        $_SESSION['success_message'] = "Товар успешно добавлен!";
        header('Location: /seller/products/products_list.php');
        exit;
    }
}

// Получаем категории для выпадающего списка
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

// Получаем бренды для выпадающего списка (если есть)
$brands = $pdo->query("SELECT id, name FROM brands ORDER BY name")->fetchAll();

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Добавить товар</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>


<main class="container mt-5">
    <h1>Добавить товар</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $field => $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
        <div class="mb-3">
            <label for="name" class="form-label">Название товара</label>
            <input type="text" name="name" id="name" class="form-control" required value="<?= htmlspecialchars($name) ?>">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Описание</label>
            <textarea name="description" id="description" class="form-control"><?= htmlspecialchars($description) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="price" class="form-label">Цена (руб.)</label>
            <input type="number" step="0.01" min="0" name="price" id="price" class="form-control" required value="<?= htmlspecialchars($price) ?>">
        </div>

        <div class="mb-3">
            <label for="category_id" class="form-label">Категория</label>
            <select name="category_id" id="category_id" class="form-select" required>
                <option value="">-- Выберите категорию --</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>" <?= ($category_id == $category['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="brand_id" class="form-label">Бренд (необязательно)</label>
            <select name="brand_id" id="brand_id" class="form-select">
                <option value="">-- Выберите бренд --</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= $brand['id'] ?>" <?= ($brand_id == $brand['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($brand['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="stock" class="form-label">Количество на складе</label>
            <input type="number" min="0" name="stock" id="stock" class="form-control" value="<?= htmlspecialchars($stock) ?>">
        </div>

        <div class="mb-3">
            <label for="slug" class="form-label">URL (slug) — если оставить пустым, будет сгенерирован автоматически</label>
            <input type="text" name="slug" id="slug" class="form-control" value="<?= htmlspecialchars($slug) ?>">
        </div>

        <div class="mb-3">
            <label for="main_image" class="form-label">Главное изображение (JPEG, PNG, GIF)</label>
            <input type="file" name="main_image" id="main_image" class="form-control" accept="image/jpeg,image/png,image/gif">
        </div>

        <button type="submit" class="btn btn-success">Добавить товар</button>
        <a href="/seller/products/products_list.php" class="btn btn-secondary">Отмена</a>
    </form>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
