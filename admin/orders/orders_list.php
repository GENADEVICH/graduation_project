<?php
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}

$search = $_GET['search'] ?? '';

try {
    // Базовый SQL-запрос с JOIN по buyer_id
    $sql = "SELECT o.id, o.total_price, o.status, o.order_date, u.username, u.email 
            FROM orders o
            JOIN users u ON o.buyer_id = u.id";

    // Если есть поиск — добавляем WHERE
    if ($search) {
        $sql .= " WHERE o.id LIKE ? OR u.username LIKE ? OR o.status LIKE ?";
    }

    $sql .= " ORDER BY o.order_date DESC";

    $stmt = $pdo->prepare($sql);

    // Выполняем запрос с параметрами поиска
    if ($search) {
        $param = "%$search%";
        $stmt->execute([$param, $param, $param]);
    } else {
        $stmt->execute();
    }

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при загрузке заказов: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список заказов</title>
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
            <i class="bi bi-receipt me-2"></i>Заказы
        </a>
        <a href="/pages/profile.php" class="btn btn-outline-danger">
            <i class="bi bi-box-arrow-right"></i> Выйти
        </a>
    </div>
</nav>

<div class="container">
    <h2 class="mb-4">Список заказов</h2>

    <!-- Форма поиска -->
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Поиск по ID, пользователю или статусу..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-outline-primary" type="submit">
                <i class="bi bi-search"></i> Поиск
            </button>
            <?php if ($search): ?>
                <a href="/admin/orders/orders_list.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Очистить
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Таблица заказов -->
    <?php if (empty($orders)): ?>
        <div class="alert alert-info">Нет заказов<?= $search ? ' по вашему запросу' : '' ?>.</div>
    <?php else: ?>
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th>Статус</th>
                    <th>Сумма</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['id']) ?></td>
                        <td>
                            <?= htmlspecialchars($order['username']) ?><br>
                            <small class="text-muted"><?= htmlspecialchars($order['email']) ?></small>
                        </td>
                        <td>
                            <?php
                            switch ($order['status']) {
                                case 'pending':
                                    echo '<span class="badge bg-warning text-dark">Ожидает оплаты</span>';
                                    break;
                                case 'paid':
                                    echo '<span class="badge bg-info">Оплачен</span>';
                                    break;
                                case 'shipped':
                                    echo '<span class="badge bg-primary">Отправлен</span>';
                                    break;
                                case 'delivered':
                                    echo '<span class="badge bg-success">Доставлен</span>';
                                    break;
                                case 'cancelled':
                                    echo '<span class="badge bg-danger">Отменён</span>';
                                    break;
                                default:
                                    echo '<span class="badge bg-secondary">Неизвестно</span>';
                            }
                            ?>
                        </td>
                        <td><?= number_format($order['total_price'], 2, ',', ' ') ?> ₽</td>
                        <td><?= htmlspecialchars($order['order_date']) ?></td>
                        <td>
                            <a href="/admin/orders/order_view.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary" title="Просмотреть"><i class="bi bi-eye"></i></a>
                            <a href="/admin/orders/edit_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary me-2">
                                <i class="bi bi-pencil"></i> Редактировать
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $order['id'] ?>">
                                <i class="bi bi-trash"></i> Удалить
                            </a>

                            <!-- Modal для удаления -->
                            <div class="modal fade" id="deleteModal<?= $order['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel">Подтверждение удаления</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                                        </div>
                                        <div class="modal-body">
                                            Вы действительно хотите удалить заказ #<?= $order['id'] ?>?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                            <a href="/admin/orders/delete_order.php?id=<?= $order['id'] ?>" class="btn btn-danger">Удалить</a>
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