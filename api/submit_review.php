<?php
// pages/submit_review.php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    header("Location: /pages/login.php");
    exit;
}

$order_id = $_POST['order_id'] ?? null;
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if (!$order_id || $rating < 1 || $rating > 5 || empty($comment)) {
    die("Пожалуйста, заполните все поля.");
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'delivered'");
$stmt->execute([$order_id, $_SESSION['user']['id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Вы не можете оставить отзыв к этому заказу.");
}

$stmt = $pdo->prepare("SELECT * FROM reviews WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user']['id']]);
if ($stmt->fetch()) {
    die("Вы уже оставили отзыв к этому заказу.");
}

$stmt = $pdo->prepare("INSERT INTO reviews (user_id, order_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->execute([$_SESSION['user']['id'], $order_id, $rating, $comment]);

header("Location: /pages/order_details.php?id=" . urlencode($order_id));
exit;
