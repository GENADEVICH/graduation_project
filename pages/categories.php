<?php
session_start();

require '../includes/db.php';
require '../includes/functions.php';

// Получаем подкатегории (parent_id IS NOT NULL)
$stmt = $pdo->query("SELECT id, name, image_url FROM categories WHERE parent_id IS NOT NULL ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔍 Раскомментируйте ниже, чтобы посмотреть, какие данные пришли из БД
// echo '<pre>'; print_r($categories); echo '</pre>'; exit;

function renderCategoryCard($category) {
    // Если изображение задано — используем его, иначе заглушка
    $image = !empty($category['image_url']) 
        ? $category['image_url'] 
        : '/assets/images/no-image.jpg';

    // Генерируем ссылку на страницу товаров по категории
    $link = '/pages/products.php?id=' . htmlspecialchars($category['id']);

    // Вывод HTML карточки
    echo '<div class="category-card">';
    echo '<a href="' . $link . '" class="card-link">';
    echo '<div class="card">';
    echo '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($category['name']) . '" class="card-img">';
    echo '<div class="card-title">' . htmlspecialchars($category['name']) . '</div>';
    echo '</div>';
    echo '</a>';
    echo '</div>';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Каталог категорий</title>
    <style>
        body {
            margin: 0;
            font-family: sans-serif;
            background: #f9f9f9;
        }
        .container {
            padding: 1rem;
        }
        h2 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        .categories-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: space-between;
        }
        .category-card {
            width: 32%;
        }
        .card-link {
            text-decoration: none;
            color: inherit;
        }
        .card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: scale(1.03);
        }
        .card-img {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            background-color: #f0f0f0;
        }
        .card-title {
            text-align: center;
            padding: 8px;
            font-size: 14px;
        }
        @media (max-width: 400px) {
            .category-card {
                width: 48%;
            }
        }
    </style>
</head>
<body>


<div class="container">
    <h2>Категории</h2>

    <?php if (!empty($categories)): ?>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
                <!-- Debug: <?= htmlspecialchars($category['name']) ?> -->
                <?php renderCategoryCard($category); ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div>Подкатегории не найдены.</div>
    <?php endif; ?>
</div>

</body>
</html>