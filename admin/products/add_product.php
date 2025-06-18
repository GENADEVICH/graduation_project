<?php
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $image_url = trim($_POST['image_url'] ?? '');

    // Заполняем обязательные значения
    $stock = $_POST['quantity'] ?? 0;

    // Можно задать заглушки (или расширить форму позже)
    $brand_id = null;
    $seller_id = null;
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)); // простой slug

    // Валидация
    if ($name === '') {
        $errors[] = 'Название обязательно.';
    }
    if (!is_numeric($price) || $price < 0) {
        $errors[] = 'Неверная цена.';
    }
    if (!is_numeric($stock) || $stock < 0) {
        $errors[] = 'Неверное количество.';
    }
    if (!ctype_digit($category_id)) {
        $errors[] = 'Выберите категорию.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products 
                    (name, description, price, category_id, brand_id, slug, main_image, seller_id, stock, created_at)
                VALUES 
                    (:name, :description, :price, :category_id, :brand_id, :slug, :main_image, :seller_id, :stock, NOW())
            ");

            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':category_id' => $category_id,
                ':brand_id' => $brand_id,
                ':slug' => $slug,
                ':main_image' => $image_url,
                ':seller_id' => $seller_id,
                ':stock' => $stock
            ]);

            header('Location: /admin/products/products_list.php?success=1');
            exit;
        } catch (PDOException $e) {
            echo "<div style='color:red;'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        foreach ($errors as $err) {
            echo "<div style='color:red;'>Ошибка: " . htmlspecialchars($err) . "</div>";
        }
    }
} else {
    echo "<div style='color:orange;'>Форма не отправлена.</div>";
}
