<?php
session_start();
require '../../includes/db.php';

$search = $_GET['search'] ?? '';

try {
    if ($search) {
        $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE username LIKE ? OR email LIKE ? ORDER BY created_at DESC");
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt = $pdo->query("SELECT id, username, email, role FROM users ORDER BY created_at DESC");
    }
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при загрузке пользователей: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список пользователей</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="/admin/dashboard.php">
            <i class="bi bi-person-lines-fill me-2"></i>Пользователи
        </a>
        <a href="/admin/logout.php" class="btn btn-outline-danger">
            <i class="bi bi-box-arrow-right"></i> Выйти
        </a>
    </div>
</nav>

<div class="container">
    <h2 class="mb-4">Список пользователей</h2>

    <!-- Форма поиска -->
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Поиск по имени или email..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-outline-primary" type="submit">
                <i class="bi bi-search"></i> Поиск
            </button>
            <?php if ($search): ?>
                <a href="/admin/users/users_list.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Очистить
                </a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($users)): ?>
        <div class="alert alert-info">Нет зарегистрированных пользователей<?= $search ? ' по вашему запросу' : '' ?>.</div>
    <?php else: ?>
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Имя пользователя</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <?= htmlspecialchars($user['role']) === 'admin' ? 
                                '<span class="badge bg-primary">Администратор</span>' : 
                                '<span class="badge bg-secondary">' . ucfirst(htmlspecialchars($user['role'])) . '</span>' ?>
                        </td>
                        <td>
                            <a href="/admin/users/edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary me-2">
                                <i class="bi bi-pencil"></i> Редактировать
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $user['id'] ?>">
                                <i class="bi bi-trash"></i> Удалить
                            </a>

                            <!-- Modal для удаления -->
                            <div class="modal fade" id="deleteModal<?= $user['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel">Подтверждение удаления</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                                        </div>
                                        <div class="modal-body">
                                            Вы действительно хотите удалить пользователя <?= htmlspecialchars($user['username']) ?>?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                            <a href="/admin/users/delete_user.php?id=<?= $user['id'] ?>" class="btn btn-danger">Удалить</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>