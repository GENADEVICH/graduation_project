<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '../../includes/functions.php'; // Подключение функций
require '../../includes/db.php'; // Подключение к базе данных

// Проверка авторизации
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php'); // Перенаправляем неавторизованных пользователей
}

// Получение параметров для сортировки и фильтрации
$sort = $_GET['sort'] ?? 'id'; // Поле для сортировки
$order = $_GET['order'] ?? 'ASC'; // Направление сортировки (ASC или DESC)
$search = $_GET['search'] ?? ''; // Поисковый запрос

// SQL-запрос для получения товаров
$query = "SELECT id, name, description, price, category_id, main_image FROM products WHERE 1=1";

// Добавление поиска
if (!empty($search)) {
    $query .= " AND (name LIKE :search OR description LIKE :search)";
}

// Добавление сортировки
$query .= " ORDER BY $sort $order";

// Подготовка запроса
$stmt = $pdo->prepare($query);

// Привязка параметров
if (!empty($search)) {
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
}

// Выполнение запроса
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список товаров</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Список товаров</h1>

        <!-- Форма поиска -->
        <form method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Поиск по названию или описанию" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Поиск</button>
            </div>
        </form>

        <!-- Таблица товаров -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>
                        <a href="?sort=id&order=<?= ($sort === 'id' && $order === 'ASC') ? 'DESC' : 'ASC' ?>">ID</a>
                    </th>
                    <th>
                        <a href="?sort=name&order=<?= ($sort === 'name' && $order === 'ASC') ? 'DESC' : 'ASC' ?>">Название</a>
                    </th>
                    <th>Описание</th>
                    <th>
                        <a href="?sort=price&order=<?= ($sort === 'price' && $order === 'ASC') ? 'DESC' : 'ASC' ?>">Цена</a>
                    </th>
                    <th>Категория</th>
                    <th>Изображение</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Товары не найдены.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['id']) ?></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($product['description'], 0, 50, "...")) ?></td>
                            <td><?= htmlspecialchars($product['price']) ?> ₽</td>
                            <td><?= htmlspecialchars($product['category_id']) ?></td>
                            <td>
                                <?php if (!empty($product['main_image'])): ?>
                                    <img src="<?= htmlspecialchars($product['main_image']) ?>" alt="Изображение товара" width="50">
                                <?php else: ?>
                                    <span>Нет изображения</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/admin/products/edit_product.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-primary">Редактировать</a>
                                <a href="/admin/products/delete_product.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Вы уверены, что хотите удалить этот товар?')">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="/admin/products/add_product.php" class="btn btn-success mb-3">Добавить товар</a>
        <a href="/admin/dashboard.php" class="btn btn-secondary">Назад</a>
    </div>
</body>
</html>