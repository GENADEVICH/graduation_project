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
$success = ''; // Сообщение об успешном оформлении заказа

// Проверяем, есть ли товары в корзине
if (empty($_SESSION['cart'])) {
    $errors['cart'] = "Ваша корзина пуста.";
}

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $full_name = trim($_POST['full_name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    $phone = trim($_POST['phone']);
    $payment_method = $_POST['payment_method'];

    // Валидация полей
    if (empty($full_name)) {
        $errors['full_name'] = "Введите ваше имя.";
    }
    if (empty($address)) {
        $errors['address'] = "Введите адрес доставки.";
    }
    if (empty($city)) {
        $errors['city'] = "Введите город.";
    }
    if (empty($postal_code)) {
        $errors['postal_code'] = "Введите почтовый индекс.";
    }
    if (empty($phone)) {
        $errors['phone'] = "Введите номер телефона.";
    }

    // Если нет ошибок, сохраняем заказ в базу данных
    if (empty($errors)) {
        $user_id = $_SESSION['user_id'];
        $total_price = 0;

        // Вычисляем общую стоимость заказа
        foreach ($_SESSION['cart'] as $item) {
            $total_price += $item['price'] * $item['quantity'];
        }

        try {
            // Начинаем транзакцию
            $pdo->beginTransaction();

            // Добавляем заказ в таблицу orders
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, full_name, address, city, postal_code, phone, payment_method, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $full_name, $address, $city, $postal_code, $phone, $payment_method, $total_price]);

            // Получаем ID созданного заказа
            $order_id = $pdo->lastInsertId();

            // Добавляем товары из корзины в таблицу order_items
            foreach ($_SESSION['cart'] as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
            }

            // Очищаем корзину
            unset($_SESSION['cart']);

            // Фиксируем транзакцию
            $pdo->commit();

            // Устанавливаем сообщение об успехе
            $success = "Заказ успешно оформлен!";
        } catch (PDOException $e) {
            // Откатываем транзакцию в случае ошибки
            $pdo->rollBack();
            $errors['general'] = "Произошла ошибка при оформлении заказа: " . $e->getMessage();
        }
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
        <?php elseif ($success): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
            <a href="/pages/home.php" class="btn btn-primary d-block mx-auto">Вернуться на главную</a>
        <?php else: ?>
            <form method="POST" action="/pages/checkout.php" class="row g-3">
                <!-- Полное имя -->
                <div class="col-md-6">
                    <label for="full_name" class="form-label">Полное имя</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" placeholder="Иван Иванов">
                    <?php if (!empty($errors['full_name'])): ?>
                        <div class="text-danger"><?= htmlspecialchars($errors['full_name']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Адрес -->
                <div class="col-md-6">
                    <label for="address" class="form-label">Адрес доставки</label>
                    <input type="text" id="address" name="address" class="form-control" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" placeholder="ул. Ленина, д. 10">
                    <?php if (!empty($errors['address'])): ?>
                        <div class="text-danger"><?= htmlspecialchars($errors['address']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Город -->
                <div class="col-md-4">
                    <label for="city" class="form-label">Город</label>
                    <input type="text" id="city" name="city" class="form-control" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" placeholder="Москва">
                    <?php if (!empty($errors['city'])): ?>
                        <div class="text-danger"><?= htmlspecialchars($errors['city']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Почтовый индекс -->
                <div class="col-md-4">
                    <label for="postal_code" class="form-label">Почтовый индекс</label>
                    <input type="text" id="postal_code" name="postal_code" class="form-control" value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>" placeholder="123456">
                    <?php if (!empty($errors['postal_code'])): ?>
                        <div class="text-danger"><?= htmlspecialchars($errors['postal_code']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Телефон -->
                <div class="col-md-4">
                    <label for="phone" class="form-label">Телефон</label>
                    <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+7 (999) 123-45-67">
                    <?php if (!empty($errors['phone'])): ?>
                        <div class="text-danger"><?= htmlspecialchars($errors['phone']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Способ оплаты -->
                <div class="col-md-12">
                    <label for="payment_method" class="form-label">Способ оплаты</label>
                    <select id="payment_method" name="payment_method" class="form-select">
                        <option value="cash" <?= isset($_POST['payment_method']) && $_POST['payment_method'] === 'cash' ? 'selected' : '' ?>>Наличными при получении</option>
                        <option value="card" <?= isset($_POST['payment_method']) && $_POST['payment_method'] === 'card' ? 'selected' : '' ?>>Оплата картой онлайн</option>
                    </select>
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