<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '../../includes/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header('Location: /pages/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller_id = $stmt->fetchColumn();

$product_id = $_GET['id'] ?? null;

if (!$product_id) {
    die("Некорректный ID товара.");
}

$product = getProductById($pdo, $product_id);
if (!$product) {
    die("Товар не найден.");
}

if ($product['seller_id'] != $seller_id) {
    die("Вы не можете удалить чужой товар.");
}

try {
    // Удаляем связанные характеристики
    $stmt = $pdo->prepare("DELETE FROM product_characteristics WHERE product_id = ?");
    $stmt->execute([$product_id]);

    // Можно удалить товар
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);

    // Удаление изображения с сервера
    if (!empty($product['main_image']) && file_exists($product['main_image'])) {
        unlink($product['main_image']);
    }

    $_SESSION['success_message'] = "Товар успешно удалён.";
    header('Location: products_list.php');
    exit;

} catch (PDOException $e) {
    die("Ошибка при удалении товара: " . $e->getMessage());
}
