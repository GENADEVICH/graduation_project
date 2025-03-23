<?php


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

// SQL-запрос для получения пользователей
$query = "SELECT id, username, email, role, is_blocked FROM users WHERE 1=1";

// Добавление поиска
if (!empty($search)) {
    $query .= " AND (username LIKE :search OR email LIKE :search)";
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
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список пользователей</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Список пользователей</h1>

        <!-- Форма поиска -->
        <form method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Поиск по имени или email" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Поиск</button>
            </div>
        </form>

        <!-- Таблица пользователей -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>
                        <a href="?sort=id&order=<?= ($sort === 'id' && $order === 'ASC') ? 'DESC' : 'ASC' ?>">ID</a>
                    </th>
                    <th>
                        <a href="?sort=username&order=<?= ($sort === 'username' && $order === 'ASC') ? 'DESC' : 'ASC' ?>">Имя пользователя</a>
                    </th>
                    <th>
                        <a href="?sort=email&order=<?= ($sort === 'email' && $order === 'ASC') ? 'DESC' : 'ASC' ?>">Email</a>
                    </th>
                    <th>Роль</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Пользователи не найдены.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td>
                                <?= $user['is_blocked'] ? '<span class="badge bg-danger">Заблокирован</span>' : '<span class="badge bg-success">Активен</span>' ?>
                            </td>
                            <td>
                                <a href="/admin/users/edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">Редактировать</a>
                                <a href="/admin/users/delete_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?')">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="/admin/dashboard.php" class="btn btn-secondary">Назад</a>
    </div>
</body>
</html>