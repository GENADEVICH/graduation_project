<?php
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}

// Обработка одобрения/отклонения заявки
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($request_id && in_array($action, ['approve', 'reject'])) {
        try {
            $stmt = $pdo->prepare("UPDATE sellers SET status = ? WHERE id = ?");
            $stmt->execute([$action, $request_id]);
        } catch (PDOException $e) {
            die("Ошибка при обновлении статуса: " . $e->getMessage());
        }
    }

    // Перезагружаем страницу после обработки
    header("Location: seller_requests.php");
    exit;
}

// Получение всех заявок с данными пользователя
try {
    $stmt = $pdo->query("
        SELECT s.*, u.username, u.email 
        FROM sellers s 
        JOIN users u ON s.user_id = u.id 
        ORDER BY s.created_at DESC
    ");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка загрузки заявок: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заявки на регистрацию продавцов</title>
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
    <a class="navbar-brand fs-4" href="/admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Панель управления</a>
        <a href="/pages/profile.php" class="btn btn-outline-danger">
            <i class="bi bi-box-arrow-right"></i> Выйти
        </a>
    </div>
</nav>

<div class="container">
    <h2 class="mb-4">Заявки на регистрацию как продавец</h2>

    <?php if (empty($requests)): ?>
        <div class="alert alert-info">Нет заявок на регистрацию.</div>
    <?php else: ?>
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th>ИНН</th>
                    <th>Сфера деятельности</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['id']) ?></td>
                        <td>
                            <?= htmlspecialchars($req['username']) ?><br>
                            <small class="text-muted"><?= htmlspecialchars($req['email']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($req['inn']) ?></td>
                        <td><?= htmlspecialchars($req['activity_field']) ?></td>
                        <td>
                            <?php
                            switch ($req['status']) {
                                case 'pending':
                                    echo '<span class="badge bg-warning">Ожидает</span>';
                                    break;
                                case 'approved':
                                    echo '<span class="badge bg-success">Одобрена</span>';
                                    break;
                                case 'rejected':
                                    echo '<span class="badge bg-danger">Отклонена</span>';
                                    break;
                                default:
                                    echo '<span class="badge bg-secondary">Неизвестно</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                                    <i class="bi bi-check-circle"></i> Одобрить
                                </button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">
                                    <i class="bi bi-x-circle"></i> Отклонить
                                </button>
                            </form>
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