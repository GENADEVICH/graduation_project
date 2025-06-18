<?php
// admin/users/seller_requests.php

session_start();
require '../../includes/db.php';

// Проверка роли администратора
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}

// Обработка изменения статуса заявки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $id = $_POST['request_id'];
    $action = in_array($_POST['action'], ['approve', 'reject']) ? $_POST['action'] : 'pending';

    // Обновляем статус заявки
    $stmt = $pdo->prepare("UPDATE sellers SET status = ? WHERE id = ?");
    $stmt->execute([$action, $id]);

    // Перенаправляем обратно на страницу заявок
    header("Location: seller_requests.php");
    exit;
}

// Получаем все заявки на регистрацию продавцов
$stmt = $pdo->query("SELECT s.*, u.username, u.email as user_email FROM sellers s JOIN users u ON s.user_id = u.id");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заявки на регистрацию продавцов</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap @5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Заявки на регистрацию продавцов</h2>

    <?php if (empty($requests)): ?>
        <div class="alert alert-info">Нет заявок.</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>ИНН</th>
                <th>Сфера</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req['id']) ?></td>
                    <td><?= htmlspecialchars($req['username']) ?> (<?= htmlspecialchars($req['user_email']) ?>)</td>
                    <td><?= htmlspecialchars($req['inn']) ?></td>
                    <td><?= htmlspecialchars($req['activity_field']) ?></td>
                    <td>
                        <?php
                        switch ($req['status']) {
                            case 'pending': echo '<span class="badge bg-warning">Ожидает</span>'; break;
                            case 'approved': echo '<span class="badge bg-success">Одобрена</span>'; break;
                            case 'rejected': echo '<span class="badge bg-danger">Отклонена</span>'; break;
                        }
                        ?>
                    </td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Одобрить</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Отклонить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>