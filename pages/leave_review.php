<?php
// pages/leave_review.php

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: /pages/login.php');
    exit;
}

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header('Location: /pages/login.php');
    exit;
}

// Получаем данные из POST
$order_id = $_POST['order_id'] ?? null;
$product_id = $_POST['product_id'] ?? null;

// Если данные отсутствуют - ошибка
if (!$order_id || !$product_id) {
    die('Некорректные данные заказа или товара.');
}

$error = '';
$success = '';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    $rating = (int)($_POST['rating']);
    $comment = trim($_POST['comment'] ?? '');

    // Валидация
    if ($rating < 1 || $rating > 5) {
        $error = 'Пожалуйста, выберите корректный рейтинг от 1 до 5.';
    } else {
        try {
            // Проверяем, что заказ принадлежит пользователю и товар в заказе
            $stmt = $pdo->prepare("
                SELECT o.id FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE o.id = ? AND o.buyer_id = ? AND oi.product_id = ?
            ");
            $stmt->execute([$order_id, $userId, $product_id]);

            $orderExists = $stmt->fetchColumn();

            if (!$orderExists) {
                $error = 'Заказ или товар не найдены, либо у вас нет прав для оставления отзыва.';
            } else {
                // Проверяем, не оставлен ли отзыв ранее
                $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND order_id = ? AND product_id = ?");
                $stmt->execute([$userId, $order_id, $product_id]);
                $existingReview = $stmt->fetchColumn();

                if ($existingReview) {
                    $error = 'Вы уже оставили отзыв на этот товар в данном заказе.';
                } else {
                    // Вставляем отзыв
                    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, order_id, product_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$userId, $order_id, $product_id, $rating, $comment]);
                    $success = 'Спасибо за отзыв!';
                }
            }
        } catch (PDOException $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Оставить отзыв</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container mt-4">
    <h2>Оставить отзыв на товар</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <a href="/pages/order_details.php?id=<?= htmlspecialchars($order_id) ?>" class="btn btn-primary">Вернуться к заказу</a>
    <?php else: ?>
        <form method="POST" action="/pages/leave_review.php" class="mt-3">
            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>" />
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($product_id) ?>" />

            <div class="mb-3">
                <label for="rating" class="form-label">Рейтинг (1-5):</label>
                <select name="rating" id="rating" class="form-select" required>
                    <option value="">Выберите рейтинг</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="comment" class="form-label">Комментарий (необязательно):</label>
                <textarea name="comment" id="comment" class="form-control" rows="4" placeholder="Напишите ваш отзыв"></textarea>
            </div>

            <button type="submit" class="btn btn-success">Отправить отзыв</button>
            <a href="/pages/order_details.php?id=<?= htmlspecialchars($order_id) ?>" class="btn btn-secondary ms-2">Отмена</a>
        </form>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
