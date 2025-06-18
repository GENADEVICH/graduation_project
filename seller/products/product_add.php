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

$user_id = $_SESSION['user_id'];

// Получаем seller_id по user_id из sellers
$stmt = $pdo->prepare("SELECT id FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    die("Продавец не найден для пользователя с ID = $user_id");
}

$seller_id = $seller['id'];

$errors = [];
$name = '';
$description = '';
$price = '';
$category_id = '';
$brand_id = '';
$stock = 0;
$slug = '';
$main_image = '';
$characteristics = []; // Здесь будем хранить характеристики для вывода

// Получаем категории и бренды для выпадающих списков
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$brands = $pdo->query("SELECT id, name FROM brands ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $brand_id = $_POST['brand_id'] ?? null;
    $stock = $_POST['stock'] ?? 0;
    $slug = trim($_POST['slug'] ?? '');

    // Загрузим характеристики для выбранной категории (чтобы потом использовать для валидации и сохранения)
    if (!empty($category_id) && is_numeric($category_id)) {
        $stmt = $pdo->prepare("SELECT * FROM characteristics WHERE category_id = ? ORDER BY name");
        $stmt->execute([$category_id]);
        $characteristics = $stmt->fetchAll();
    } else {
        // Если категория не выбрана — грузим все характеристики (можно изменить логику)
        $characteristics = $pdo->query("SELECT * FROM characteristics ORDER BY name")->fetchAll();
    }

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
            $upload_dir = __DIR__ . '/../../uploads/products/';
            $destination = $upload_dir . $filename;

            // Создаем папку, если не существует
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            if (!move_uploaded_file($_FILES['main_image']['tmp_name'], $destination)) {
                $errors['main_image'] = 'Не удалось сохранить изображение.';
            } else {
                // Записываем относительный путь для хранения в БД
                $main_image = '/uploads/products/' . $filename;
            }
        }
    }


    // Валидация характеристик (пример: для integer и decimal — проверить число, для boolean — checkbox)
    if (!empty($_POST['characteristics']) && is_array($_POST['characteristics'])) {
        foreach ($_POST['characteristics'] as $char_id => $val) {
            // Найдем тип характеристики
            $type = null;
            foreach ($characteristics as $ch) {
                if ($ch['id'] == $char_id) {
                    $type = $ch['value_type'];
                    break;
                }
            }
            if ($type === null) continue; // невалидный ID характеристики

            if ($type === 'integer' && !is_numeric($val)) {
                $errors['characteristics'][$char_id] = 'Введите целое число для характеристики "' . htmlspecialchars($ch['name']) . '".';
            }
            if ($type === 'decimal' && !is_numeric($val)) {
                $errors['characteristics'][$char_id] = 'Введите число (с точкой) для характеристики "' . htmlspecialchars($ch['name']) . '".';
            }
            // Для boolean не нужно — checkbox
        }
    }

    if (empty($errors)) {
        // Теперь вставляем в products
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, brand_id, slug, main_image, seller_id, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name,
            $description,
            $price,
            $category_id,
            $brand_id ?: null,
            $slug,
            $main_image,
            $seller_id,
            $stock
        ]);

        $product_id = $pdo->lastInsertId();

        // Вставляем характеристики
        if (!empty($_POST['characteristics']) && is_array($_POST['characteristics'])) {
            $stmtChar = $pdo->prepare("INSERT INTO product_characteristics (product_id, characteristic_id, value_string, value_integer, value_decimal, value_boolean) VALUES (?, ?, ?, ?, ?, ?)");

            foreach ($_POST['characteristics'] as $char_id => $val) {
                $type = null;
                foreach ($characteristics as $ch) {
                    if ($ch['id'] == $char_id) {
                        $type = $ch['value_type'];
                        break;
                    }
                }
                if ($type === null) continue;

                $value_string = null;
                $value_integer = null;
                $value_decimal = null;
                $value_boolean = null;

                switch ($type) {
                    case 'string':
                        $value_string = trim($val);
                        break;
                    case 'integer':
                        $value_integer = is_numeric($val) ? (int)$val : null;
                        break;
                    case 'decimal':
                        $value_decimal = is_numeric($val) ? (float)$val : null;
                        break;
                    case 'boolean':
                        $value_boolean = !empty($val) ? 1 : 0;
                        break;
                }

                $stmtChar->execute([
                    $product_id,
                    $char_id,
                    $value_string,
                    $value_integer,
                    $value_decimal,
                    $value_boolean
                ]);
            }
        }

        $_SESSION['success_message'] = "Товар успешно добавлен!";
        header('Location: /seller/products/products_list.php');
        exit;
    }
} else {
    // Если GET-запрос — загружаем характеристики для выбранной категории (если есть)
    if (!empty($category_id) && is_numeric($category_id)) {
        $stmt = $pdo->prepare("SELECT * FROM characteristics WHERE category_id = ? ORDER BY name");
        $stmt->execute([$category_id]);
        $characteristics = $stmt->fetchAll();
    }
}

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
                    <?php 
                        if (is_array($error)) {
                            foreach ($error as $subErr) {
                                echo '<li>' . htmlspecialchars($subErr) . '</li>';
                            }
                        } else {
                            echo '<li>' . htmlspecialchars($error) . '</li>';
                        }
                    ?>
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
            <small class="text-muted">После выбора категории характеристики обновятся автоматически.</small>
        </div>

        <div class="mb-3">
            <label for="brand_id" class="form-label">Бренд (необязательно)</label>
            <select name="brand_id" id="brand_id" class="form-select">
                <option value="">-- Не выбран --</option>
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
            <label for="slug" class="form-label">URL (slug)</label>
            <input type="text" name="slug" id="slug" class="form-control" value="<?= htmlspecialchars($slug) ?>">
            <small class="text-muted">Если оставить пустым, будет сгенерирован автоматически из названия.</small>
        </div>

        <div class="mb-3">
            <label for="main_image" class="form-label">Главное изображение</label>
            <input type="file" name="main_image" id="main_image" class="form-control" accept="image/*">
        </div>
        <div id="characteristics-section">
        <?php if (!empty($characteristics)): ?>
            <h3>Характеристики</h3>
            <?php foreach ($characteristics as $ch): ?>
                <div class="mb-3">
                    <label for="char_<?= $ch['id'] ?>" class="form-label"><?= htmlspecialchars($ch['name']) ?></label>
                    <?php 
                        $val = $_POST['characteristics'][$ch['id']] ?? '';
                        switch ($ch['value_type']) {
                            case 'string':
                                echo '<input type="text" name="characteristics[' . $ch['id'] . ']" id="char_' . $ch['id'] . '" class="form-control" value="' . htmlspecialchars($val) . '">';
                                break;
                            case 'integer':
                                echo '<input type="number" name="characteristics[' . $ch['id'] . ']" id="char_' . $ch['id'] . '" class="form-control" value="' . htmlspecialchars($val) . '">';
                                break;
                            case 'decimal':
                                echo '<input type="number" step="0.01" name="characteristics[' . $ch['id'] . ']" id="char_' . $ch['id'] . '" class="form-control" value="' . htmlspecialchars($val) . '">';
                                break;
                            case 'boolean':
                                $checked = ($val) ? 'checked' : '';
                                echo '<input type="checkbox" name="characteristics[' . $ch['id'] . ']" id="char_' . $ch['id'] . '" value="1" ' . $checked . '>';
                                break;
                        }
                    ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">Добавить товар</button>
    </form>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('category_id').addEventListener('change', function() {
    const categoryId = this.value;
    const characteristicsSection = document.querySelector('#characteristics-section');
    
    if (!categoryId) {
        characteristicsSection.innerHTML = '';
        return;
    }

    fetch('/seller/products/get_characteristics.php?category_id=' + categoryId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            characteristicsSection.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            characteristicsSection.innerHTML = '<div class="alert alert-danger">Ошибка загрузки характеристик</div>';
        });
});
</script>

</body>
</html>
