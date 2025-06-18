<?php
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if (!$id || !ctype_digit($id)) {
        $errors[] = 'Неверный ID товара.';
    }

    // Получение текущих данных товара
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $errors[] = 'Товар не найден.';
    }

    if (empty($errors)) {
        // Новые значения или старые, если поля не изменялись
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? '';
        $stock = $_POST['quantity'] ?? '';
        $category_id = $_POST['category_id'] ?? '';
        $main_image = trim($_POST['image_url'] ?? '');

        $name = $name !== '' ? $name : $product['name'];
        $description = $description !== '' ? $description : $product['description'];
        $price = is_numeric($price) ? $price : $product['price'];
        $stock = is_numeric($stock) ? $stock : $product['stock'];
        $category_id = ctype_digit($category_id) ? $category_id : $product['category_id'];
        $main_image = $main_image !== '' ? $main_image : $product['main_image'];
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));

        try {
            $stmt = $pdo->prepare("
                UPDATE products SET
                    name = :name,
                    description = :description,
                    price = :price,
                    category_id = :category_id,
                    brand_id = :brand_id,
                    slug = :slug,
                    main_image = :main_image,
                    seller_id = :seller_id,
                    stock = :stock
                WHERE id = :id
            ");

            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':category_id' => $category_id,
                ':brand_id' => $product['brand_id'],     // не изменяется
                ':slug' => $slug,
                ':main_image' => $main_image,
                ':seller_id' => $product['seller_id'],   // не изменяется
                ':stock' => $stock,
                ':id' => $id
            ]);

            header('Location: /admin/products/products_list.php?updated=1');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }

    // Возврат с ошибками
    $_SESSION['errors'] = $errors;
    header("Location: /admin/products/product_edit.php?id=$id");
    exit;
}
?>
