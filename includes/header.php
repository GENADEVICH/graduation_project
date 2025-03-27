<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Запуск сессии
}

// Подключение к базе данных
require_once __DIR__ . '/../includes/db.php';

// Функция для построения дерева категорий
function buildCategoryTree($categories, $parentId = null) {
    $tree = [];
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parentId) {
            $children = buildCategoryTree($categories, $category['id']);
            if (!empty($children)) {
                $category['children'] = $children;
            }
            $tree[] = $category;
        }
    }
    return $tree;
}

// Получаем все категории из базы данных
$stmt = $pdo->query("SELECT id, name, parent_id FROM categories ORDER BY parent_id ASC, name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Строим дерево категорий
$categoryTree = buildCategoryTree($categories);

// Функция для рендеринга дерева категорий
function renderCategoryMenu($categories) {
    echo '<ul class="dropdown-menu">';
    foreach ($categories as $category) {
        echo '<li>';
        echo '<a class="dropdown-item" href="/category/category.php?id=' . $category['id'] . '">' . htmlspecialchars($category['name']) . '</a>';
        if (!empty($category['children'])) {
            renderCategoryMenu($category['children']); // Рекурсивный вызов для подкатегорий
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Маркетплейс Zona</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Стили -->
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <header class="bg-primary text-white py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <!-- Левая часть: Логотип и Каталог -->
            <div class="d-flex align-items-center">
                <a href="/pages/home.php" class="text-white text-decoration-none me-3">
                    <h1 class="mb-0">Zona</h1>
                </a>
                <div class="dropdown">
                    <button class="btn btn-light bi-list" type="button" id="catalogDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Каталог
                    </button>
                    <?php
                    // Рендеринг дерева категорий
                    renderCategoryMenu($categoryTree);
                    ?>
                </div>
            </div>

            <!-- Центральная часть: Поиск -->
            <div class="flex-grow-1 mx-3 position-relative">
                <form id="search-form" action="/pages/home.php" method="GET">
                    <div class="input-group">
                        <input type="text" id="search-input" name="search" class="form-control" placeholder="Поиск товаров...">
                        <button type="submit" class="btn btn-light">Найти</button>
                    </div>
                </form>
                <ul id="search-results" class="list-group position-absolute w-100" style="display: none; z-index: 1000;"></ul>
            </div>

            <!-- Правая часть: Навигация -->
            <nav class="d-flex align-items-center gap-3">
                <?php if (isLoggedIn()): ?>
                    <a href="/pages/profile.php" class="text-white text-decoration-none">Профиль</a>
                <?php else: ?>
                    <a href="/pages/login.php" class="text-white text-decoration-none">Войти</a>
                <?php endif; ?>
                <a href="/pages/orders.php" class="text-white text-decoration-none">Заказы</a>
                <a href="/pages/wishlist.php" class="text-white text-decoration-none position-relative">
                    Избранное
                    <?php if (!empty($_SESSION['wishlist'])): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= count($_SESSION['wishlist']) ?>
                        </span>
                    <?php endif; ?>
                </a>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Проверка работы Bootstrap JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof bootstrap === 'undefined') {
                console.error("Bootstrap не загружен! Проверь подключение.");
            }
        });
    </script>

    <!-- Исправление работы каталога -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let dropdownToggle = document.getElementById("catalogDropdown");
            let dropdownMenu = document.querySelector(".dropdown-menu");

            if (dropdownToggle && dropdownMenu) {
                dropdownToggle.addEventListener("click", function (event) {
                    event.stopPropagation();
                    dropdownMenu.classList.toggle("show");
                });

                document.addEventListener("click", function (event) {
                    if (!dropdownToggle.contains(event.target) && !dropdownMenu.contains(event.target)) {
                        dropdownMenu.classList.remove("show");
                    }
                });
            }
        });
    </script>

    <!-- Скрипт для автодополнения поиска -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('search-input');
            const searchResults = document.getElementById('search-results');

            if (searchInput) {
                searchInput.addEventListener('input', async (event) => {
                    const query = event.target.value.trim();
                    if (query.length < 2) {
                        searchResults.innerHTML = '';
                        searchResults.style.display = 'none';
                        return;
                    }
                    try {
                        const response = await fetch(`/api/search.php?query=${encodeURIComponent(query)}`);
                        const products = await response.json();
                        searchResults.innerHTML = '';
                        if (products.length > 0) {
                            products.forEach(product => {
                                const li = document.createElement('li');
                                li.className = 'list-group-item';
                                li.textContent = product.name;
                                li.addEventListener('click', () => {
                                    window.location.href = `/pages/product.php?id=${product.id}`;
                                });
                                searchResults.appendChild(li);
                            });
                            searchResults.style.display = 'block';
                        } else {
                            searchResults.style.display = 'none';
                        }
                    } catch (error) {
                        console.error('Ошибка при выполнении запроса:', error);
                    }
                });
            }
            document.addEventListener('click', (event) => {
                if (!searchResults.contains(event.target)) {
                    searchResults.innerHTML = '';
                    searchResults.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>