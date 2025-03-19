<?php
// includes/header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Запускаем сессию, если она еще не запущена
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Маркетплейс</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons (опционально) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Твои собственные стили -->
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <header class="bg-primary text-white py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <!-- Левая часть: Маркетплейс (ссылка на главную страницу) -->
            <div>
                <a href="/pages/home.php" class="text-white text-decoration-none">
                    <h1 class="mb-0">Zona</h1>
                </a>
            </div>

            <!-- Центральная часть: Поиск -->
            <div class="flex-grow-1 mx-3 position-relative">
                <form id="search-form" class="search-form" action="/pages/home.php" method="GET">
                    <div class="input-group">
                        <input type="text" id="search-input" name="search" class="form-control" placeholder="Поиск товаров...">
                        <button type="submit" class="btn btn-light">Найти</button>
                    </div>
                </form>
                <!-- Выпадающий список для автодополнения -->
                <ul id="search-results" class="list-group position-absolute w-100" style="display: none; z-index: 1000;"></ul>
            </div>

            <!-- Правая часть: Навигация -->
            <nav class="d-flex gap-3 align-items-center">
                <a href="/pages/home.php" class="text-white text-decoration-none">Главная</a>
                <?php if (isLoggedIn()): ?>
                    <a href="/pages/profile.php" class="text-white text-decoration-none">Профиль</a>
                    <a href="/pages/wishlist.php" class="text-white text-decoration-none position-relative">
                        Избранное
                        <?php if (!empty($_SESSION['wishlist'])): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= count($_SESSION['wishlist']) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="/pages/login.php" class="text-white text-decoration-none">Войти</a>
                <?php endif; ?>
                <a href="/pages/cart.php" class="text-white text-decoration-none position-relative">
                    Корзина
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= count($_SESSION['cart']) ?>
                        </span>
                    <?php endif; ?>
                </a>
            </nav>
        </div>
    </header>

    <!-- Bootstrap JS и зависимости -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Скрипт для автодополнения -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('search-input');
            const searchResults = document.getElementById('search-results');

            // Функция для очистки выпадающего списка
            function clearResults() {
                searchResults.innerHTML = '';
                searchResults.style.display = 'none';
            }

            // Обработка ввода текста
            searchInput.addEventListener('input', async (event) => {
                const query = event.target.value.trim();

                if (query.length < 2) {
                    clearResults();
                    return;
                }

                try {
                    // Отправляем AJAX-запрос на сервер
                    const response = await fetch(`/api/search.php?query=${encodeURIComponent(query)}`);
                    const products = await response.json();

                    // Очищаем предыдущие результаты
                    clearResults();

                    if (products.length > 0) {
                        // Добавляем товары в выпадающий список
                        products.forEach(product => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item';
                            li.textContent = product.name;
                            li.addEventListener('click', () => {
                                // При клике на товар, перенаправляем на страницу товара
                                window.location.href = `/pages/product.php?id=${product.id}`;
                            });
                            searchResults.appendChild(li);
                        });
                        searchResults.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Ошибка при выполнении запроса:', error);
                }
            });

            // Скрываем выпадающий список при клике вне поля поиска
            document.addEventListener('click', (event) => {
                if (!searchInput.contains(event.target)) {
                    clearResults();
                }
            });
        });
    </script>
</body>
</html>