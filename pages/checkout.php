<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require '../includes/db.php'; // Подключение к базе данных
require '../includes/functions.php'; // Подключение функций

// Проверка, авторизован ли пользователь
if (!isLoggedIn()) {
    redirect('/pages/login.php'); // Перенаправляем неавторизованных пользователей
}

$errors = []; // Массив для хранения ошибок

$user_id = $_SESSION['user_id'];

// Получаем товары из корзины из базы данных
$stmt = $pdo->prepare("
    SELECT p.id AS product_id, p.name, p.price, c.quantity
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Проверяем, есть ли товары в корзине
if (empty($cartItems)) {
    $errors['cart'] = "Ваша корзина пуста.";
}

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors['cart'])) {
    // Получаем данные из формы
    $shipping_address = trim($_POST['address']);
    $total_price = 0;

    // Вычисляем общую стоимость заказа
    foreach ($cartItems as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }

    try {
        // Начинаем транзакцию
        $pdo->beginTransaction();

        // Добавляем заказ в таблицу orders
        $stmt = $pdo->prepare("INSERT INTO orders (buyer_id, order_date, status, total_price, shipping_address) VALUES (?, NOW(), 'pending', ?, ?)");
        $stmt->execute([$user_id, $total_price, $shipping_address]);

        // Получаем ID созданного заказа
        $order_id = $pdo->lastInsertId();

        // Добавляем товары из корзины в таблицу order_items
        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
        }

        // Очищаем корзину
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Фиксируем транзакцию
        $pdo->commit();

        // Перенаправляем на страницу благодарности
        redirect("/pages/thank_you.php?order_id=$order_id");
    } catch (PDOException $e) {
        // Откатываем транзакцию в случае ошибки
        $pdo->rollBack();
        $errors['general'] = "Произошла ошибка при оформлении заказа: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons (опционально) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Твои собственные стили -->
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mt-4">
        <h1 class="text-center mb-4">Оформление заказа</h1>

        <?php if (!empty($errors['cart'])): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($errors['cart']) ?></div>
            <a href="/pages/cart.php" class="btn btn-primary d-block mx-auto">Перейти в корзину</a>
        <?php elseif (!empty($errors['general'])): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($errors['general']) ?></div>
        <?php else: ?>
            <form method="POST" action="/pages/checkout.php" class="row g-3">
                <!-- Адрес доставки -->
                <div class="col-md-12">
                    <label for="address" class="form-label">Адрес доставки</label>
                    <input type="text" id="address" name="address" class="form-control" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" placeholder="ул. Ленина, д. 10">
                    <?php if (!empty($errors['address'])): ?>
                        <div class="text-danger"><?= htmlspecialchars($errors['address']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Кнопка подтверждения заказа -->
                <div class="col-12">
                    <button type="submit" class="btn btn-success w-100">Подтвердить заказ</button>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <!-- Bootstrap JS и зависимости -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>