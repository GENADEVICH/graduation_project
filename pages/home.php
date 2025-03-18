<?php
// pages/home.php

require_once __DIR__ . '/../includes/db.php'; // Подключаем db.php
require_once __DIR__ . '/../includes/functions.php'; // Подключаем functions.php

// Обработка поискового запроса (для AJAX)
if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']); // Получаем поисковый запрос
    try {
        if (!empty($searchQuery)) {
            // Если есть поисковый запрос, фильтруем товары
            $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE :search OR description LIKE :search");
            $stmt->execute(['search' => "%$searchQuery%"]);
        } else {
            // Если запроса нет, выводим все товары
            $stmt = $pdo->query("SELECT * FROM products");
        }
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Возвращаем результат в формате JSON
        header('Content-Type: application/json');
        echo json_encode($products);
        exit();
    } catch (PDOException $e) {
        die("Ошибка при выполнении запроса: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная страница</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container">
        <h1>Добро пожаловать в наш маркетплейс!</h1>
        <p>Здесь вы найдете лучшие товары по выгодным ценам.</p>

        <!-- Поисковая строка -->
        <form id="search-form" class="search-form">
            <input type="text" id="search-input" name="search" placeholder="Поиск товаров...">
            <button type="submit" class="btn">Найти</button>
        </form>

        <!-- Секция для отображения товаров -->
        <section class="products">
            <h2>Товары</h2>
            <div id="product-list" class="product-list">
                <?php
                // По умолчанию отображаем все товары
                try {
                    $stmt = $pdo->query("SELECT * FROM products");
                    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    die("Ошибка при выполнении запроса: " . $e->getMessage());
                }

                if (empty($products)): ?>
                    <p>Товары отсутствуют.</p>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                            <?php else: ?>
                                <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="product-image">
                            <?php endif; ?>
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['description']) ?></p>
                            <p class="price">Цена: <?= htmlspecialchars($product['price']) ?> руб.</p>
                            <a href="/pages/product.php?id=<?= $product['id'] ?>" class="btn">Подробнее</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <!-- Подключаем JavaScript -->
    <script>
        // Функция для обработки поиска
        function handleSearch(event) {
            event.preventDefault(); // Отменяем стандартное поведение формы

            const searchQuery = document.getElementById('search-input').value; // Получаем поисковый запрос
            const productList = document.getElementById('product-list'); // Контейнер для товаров

            // Отправляем AJAX-запрос
            fetch(`/pages/home.php?search=${encodeURIComponent(searchQuery)}`)
                .then(response => response.json())
                .then(products => {
                    // Очищаем список товаров
                    productList.innerHTML = '';

                    // Если товары найдены, отображаем их
                    if (products.length > 0) {
                        products.forEach(product => {
                            const productCard = `
                                <div class="product-card">
                                    ${product.image ? `<img src="${product.image}" alt="${product.name}" class="product-image">` : `<img src="/assets/images/no-image.jpg" alt="Нет изображения" class="product-image">`}
                                    <h3>${product.name}</h3>
                                    <p>${product.description}</p>
                                    <p class="price">Цена: ${product.price} руб.</p>
                                    <a href="/pages/product.php?id=${product.id}" class="btn">Подробнее</a>
                                </div>
                            `;
                            productList.innerHTML += productCard;
                        });
                    } else {
                        // Если товары не найдены, выводим сообщение
                        productList.innerHTML = '<p>Товары не найдены.</p>';
                    }
                })
                .catch(error => {
                    console.error('Ошибка при выполнении запроса:', error);
                });
        }

        // Назначаем обработчик события для формы
        document.getElementById('search-form').addEventListener('submit', handleSearch);

        // Обработка ввода в реальном времени
        document.getElementById('search-input').addEventListener('input', handleSearch);
    </script>
</body>
</html>