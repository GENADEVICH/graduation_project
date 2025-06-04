<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user_id = $_SESSION['user_id'];

// Получаем содержимое корзины
$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.price, c.quantity
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cartItems)) {
    // Если корзина пустая — редирект обратно
    redirect('/pages/cart.php');
}

// Подсчет общей суммы
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Валидация полей
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';

    if ($name === '') $errors[] = 'Введите имя';
    if ($phone === '') $errors[] = 'Введите телефон';
    if ($address === '') $errors[] = 'Введите адрес доставки';
    if (!in_array($payment_method, ['card', 'cash'])) $errors[] = 'Выберите способ оплаты';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Вставляем заказ в таблицу orders
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, name, phone, address, payment_method, total_amount, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $name, $phone, $address, $payment_method, $total]);

            $order_id = $pdo->lastInsertId();

            // Вставляем товары заказа в таблицу order_items
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cartItems as $item) {
                $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
            }

            // Очищаем корзину пользователя
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $pdo->commit();

            $success = true;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Ошибка при оформлении заказа: ' . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Оформление заказа</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="container mt-4" style="max-width: 600px;">
    <h1 class="mb-4">Оформление заказа</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Спасибо! Ваш заказ успешно оформлен.
        </div>
        <a href="/pages/products.php" class="btn btn-primary">Вернуться к покупкам</a>
    <?php else: ?>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?=htmlspecialchars($error)?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <h5>Ваш заказ на сумму: <strong><?=number_format($total, 2, ',', ' ')?> ₽</strong></h5>

        <form method="post" novalidate>
            <div class="mb-3">
                <label for="name" class="form-label">Имя и Фамилия</label>
                <input type="text" class="form-control" id="name" name="name" required value="<?=htmlspecialchars($_POST['name'] ?? '')?>">
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Телефон</label>
                <input type="tel" class="form-control" id="phone" name="phone" required value="<?=htmlspecialchars($_POST['phone'] ?? '')?>">
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Адрес доставки</label>
                <textarea class="form-control" id="address" name="address" rows="3" required><?=htmlspecialchars($_POST['address'] ?? '')?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Способ оплаты</label>
                <select class="form-select" name="payment_method" required>
                    <option value="" disabled <?=!isset($_POST['payment_method']) ? 'selected' : ''?>>Выберите способ оплаты</option>
                    <option value="card" <?=($_POST['payment_method'] ?? '') === 'card' ? 'selected' : ''?>>Банковская карта</option>
                    <option value="cash" <?=($_POST['payment_method'] ?? '') === 'cash' ? 'selected' : ''?>>Наличные курьеру</option>
                </select>
            </div>

            <button type="submit" class="btn btn-success w-100">Подтвердить заказ</button>
            <a href="/pages/cart.php" class="btn btn-secondary w-100 mt-2">Вернуться в корзину</a>
        </form>

    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
