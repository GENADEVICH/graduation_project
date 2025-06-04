<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Запуск сессии
}
// Получаем текущий адрес страницы
$current_page = $_SERVER['REQUEST_URI'];

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
$categoryTree = buildCategoryTree($categories, null);

// Функция для рендеринга дерева категорий
function renderCategoryMenu($categories) {
    echo '<ul class="dropdown-menu">';
    foreach ($categories as $category) {
        echo '<li class="' . (!empty($category['children']) ? 'dropdown-submenu' : '') . '">';
        if (!empty($category['children'])) {
            echo '<a class="dropdown-item" href="/category/category.php?id=' . $category['id'] . '">' . htmlspecialchars($category['name']) . '</a>';
            renderCategoryMenu($category['children']);
        } else {
            echo '<a class="dropdown-item" href="/pages/products.php?id=' . $category['id'] . '">' . htmlspecialchars($category['name']) . '</a>';
        }
        echo '</li>';
    }
    echo '</ul>';
}

// Получение количества товаров в корзине и избранном
function getCartCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) AS total FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

function getWishlistCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM wishlist WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Маркетплейс Lumi</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"  rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"  rel="stylesheet">

    <style>
        /* Стиль для фиксированной шапки */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Отступ для основного контента */
        body {
            padding-top: 80px;
        }

        /* Прячем мобильный хедер на больших экранах */
        .mobile-header {
            display: none;
        }

        @media (max-width: 991.98px) {
            .desktop-header {
                display: none;
            }

            .mobile-header {
                display: block;
            }
        }

        /* Нижняя панель */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #fff;
            border-top: 1px solid #ddd;
            box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .bottom-nav ul {
            display: flex;
            justify-content: space-around;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .bottom-nav li {
            flex: 1;
            text-align: center;
        }

        .bottom-nav a {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 0;
            color: #333;
            text-decoration: none;
        }

        .bottom-nav i {
            font-size: 1.5rem;
        }

        .badge {
            top: -10px;
            padding: 5px 8px;
            border-radius: 50%;
            background-color: red;
            color: white;
            font-size: 0.7rem;
        }
        /* Мобильный поиск */
        .mobile-search {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            border-radius: 0px  0px 15px 15px;
            background-color: #fff;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 10px;
            transition: transform 0.3s ease;
        }

        .mobile-search.hidden {
            transform: translateY(-100%);
        }

        .search-form {
            width: 100%;
            margin: 0 10px;
        }

        .search-input {
            width: 100%;
            border-radius: 5px;
            border: 1px solid #ccc;
            padding: 8px 12px;
            font-size: 1rem;
            background-color: #f9f5f5a1;
        }
    </style>
</head>
<body>

<!-- Desktop Header -->
<header class="desktop-header bg-primary text-white py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <!-- Левая часть: Логотип и Каталог -->
        <div class="d-flex align-items-center">
            <a href="/pages/home.php" class="text-white text-decoration-none me-3">
                <h1 class="mb-0">Lumi</h1>
            </a>
            <div class="dropdown">
                <button class="btn btn-light bi-list" type="button" id="catalogDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    Каталог
                </button>
                <?php renderCategoryMenu($categoryTree); ?>
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
                <?php if (isLoggedIn()): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= getWishlistCount($pdo, $_SESSION['user_id']) ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="/pages/cart.php" class="text-white text-decoration-none position-relative">
                Корзина
                <?php if (isLoggedIn()): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= getCartCount($pdo, $_SESSION['user_id']) ?>
                    </span>
                <?php endif; ?>
            </a>
        </nav>
    </div>
</header>

<?php if (
    $current_page == '/' ||
    $current_page == '/pages/home.php' ||
    strpos($current_page, '/pages/products.php') !== false ||
    strpos($current_page, '/category/') !== false
): ?>
<!-- Мобильный поиск -->
<div id="mobile-search" class="mobile-search d-md-none">
    <div class="container d-flex justify-content-center">
        <form action="/pages/home.php" method="GET" class="position-relative w-100 px-3">
            <i class="bi bi-search position-absolute" style="left: 30px; top: 8px; color: #999;"></i>
            <input type="text" name="search" class="form-control form-control-sm search-input ps-5" placeholder="Искать на Lumi">
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Bottom Navigation Bar -->
<nav class="bottom-nav d-md-none">
    <ul>
        <li><a href="/pages/home.php"><i class="bi bi-house-door"></i>Главная</a></li>
        <li>
            <a href="/pages/cart.php">
                <i class="bi bi-cart-dash-fill"></i>
                <span>Корзина</span>
            </a>
        </li>
        <li>
            <a href="/pages/wishlist.php">
                <i class="bi bi-heart"></i>
                <span>Избранное</span>
            </a>
        </li>
        <li><a href="/pages/profile.php"><i class="bi bi-person-circle"></i>Профиль</a></li>
    </ul>
</nav>

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
                    const response = await fetch(`../api/search.php?query=${encodeURIComponent(query)}`);
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

    document.addEventListener('DOMContentLoaded', function () {
        const mobileSearch = document.getElementById('mobile-search');
        if (mobileSearch) {  // Проверяем, существует ли элемент
            let lastScrollTop = 0;
            window.addEventListener('scroll', function () {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                if (scrollTop > lastScrollTop) {
                    mobileSearch.classList.add('hidden');
                } else {
                    mobileSearch.classList.remove('hidden');
                }
                lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
            });
        }
    });
</script>
</body>
</html>