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
    redirect('/pages/cart.php');
}

// Подсчет общей суммы
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Загружаем данные пользователя для автозаполнения
$stmt = $pdo->prepare("SELECT first_name, last_name, email, delivery_lat, delivery_lon FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Валидация полей
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $delivery_lat = trim($_POST['delivery_lat'] ?? '');
    $delivery_lon = trim($_POST['delivery_lon'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';

    if ($first_name === '') $errors[] = 'Введите имя';
    if ($last_name === '') $errors[] = 'Введите фамилию';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Введите корректный email';
    if ($delivery_lat === '' || $delivery_lon === '') $errors[] = 'Выберите место доставки на карте';
    if (!in_array($payment_method, ['card', 'cash'])) $errors[] = 'Выберите способ оплаты';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Обновляем данные пользователя
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, delivery_lat = ?, delivery_lon = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $delivery_lat, $delivery_lon, $user_id]);

            // Генерируем уникальный номер заказа
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id = ?");
            $stmt->execute([$user_id]);
            $orderCount = $stmt->fetchColumn();
            $nextOrderNumber = $orderCount + 1;
            $orderNumber = $user_id . '-' . str_pad($nextOrderNumber, 4, '0', STR_PAD_LEFT);

            // Вставка заказа
            $stmt = $pdo->prepare("INSERT INTO orders (order_number, buyer_id, shipping_address, total_price, status) VALUES (?, ?, ?, ?, ?)");
            $address = "Координаты: $delivery_lat, $delivery_lon"; // Можно заменить на реальный адрес через геокодирование
            $stmt->execute([$orderNumber, $user_id, $address, $total, 'pending']);
            $order_id = $pdo->lastInsertId();

            // Вставка товаров
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cartItems as $item) {
                $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
            }

            // Очищаем корзину пользователя
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $pdo->commit();

            ob_start();
            ?>
            <!DOCTYPE html>
            <html lang="ru">
            <head>
                <meta charset="UTF-8">
                <title>Чек по заказу №<?=$orderNumber?></title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background-color: #f9f9f9;
                        color: #333;
                        line-height: 1.6;
                        margin: 0;
                        padding: 0;
                    }
                    .container {
                        max-width: 600px;
                        margin: auto;
                        background-color: #fff;
                        border-radius: 8px;
                        padding: 25px;
                        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                    }
                    .header {
                        text-align: center;
                        padding-bottom: 20px;
                        border-bottom: 2px solid #eee;
                    }
                    .header h2 {
                        margin: 0;
                        font-size: 24px;
                        color: #2c3e50;
                    }
                    .logo {
                        max-width: 120px;
                        margin-bottom: 10px;
                    }
                    .info p {
                        margin: 5px 0;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                    }
                    th, td {
                        padding: 10px;
                        border-bottom: 1px solid #ddd;
                        text-align: left;
                    }
                    th {
                        background-color: #f2f2f2;
                    }
                    .total {
                        text-align: right;
                        font-size: 18px;
                        font-weight: bold;
                        margin-top: 20px;
                    }
                    .footer {
                        margin-top: 30px;
                        font-size: 14px;
                        color: #777;
                        text-align: center;
                    }
                    .btn {
                        display: inline-block;
                        margin-top: 20px;
                        padding: 10px 20px;
                        background-color: #27ae60;
                        color: white;
                        text-decoration: none;
                        border-radius: 4px;
                        font-weight: bold;
                    }
                </style>
            </head>
            <body>
            <div class="container">
                <div class="header">
                    <!-- Можно заменить на свой логотип -->
                    <img src="https://cdn.discordapp.com/attachments/1218231417465606184/1382729356866224212/Group_2_1.png?ex=684c3690&is=684ae510&hm=f04e17003961132b8ee95e6b9d38c8d6b64c25c592a897cadef422964350fcbf&" alt="Логотип" class="logo">
                    <h2>Чек по заказу №<?=$orderNumber?></h2>
                    <p><strong>Дата:</strong> <?=date('d.m.Y H:i')?></p>
                </div>

                <div class="info">
                    <p><strong>Клиент:</strong> <?=$first_name?> <?=$last_name?></p>
                    <p><strong>Email:</strong> <?=$email?></p>
                    <p><strong>Способ оплаты:</strong>
                        <?=$payment_method === 'card' ? 'Банковская карта' : 'Наличные курьеру'?>
                    </p>
                </div>

                <table>
                    <thead>
                    <tr>
                        <th>Товар</th>
                        <th>Кол-во</th>
                        <th>Цена</th>
                        <th>Сумма</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td><?=$item['name']?></td>
                            <td><?=$item['quantity']?> шт.</td>
                            <td><?=number_format($item['price'], 2, ',', ' ')?> ₽</td>
                            <td><?=number_format($item['price'] * $item['quantity'], 2, ',', ' ')?> ₽</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="total">
                    Итого: <?=number_format($total, 2, ',', ' ')?> ₽
                </div>

                <div class="footer">
                    <p>© 2025 Ваш магазин. Все права защищены.</p>
                    <p><a href="https://akrapov1c.ru/pages/order_details.php?id=<?=$order_id?>" class="btn">Посмотреть заказ</a></p>
                </div>
            </div>
            </body>
            </html>
            <?php
            $mailBody = ob_get_clean();

            $subject = "Чек по заказу №$orderNumber";

            if (!send_email($email, $subject, $mailBody)) {
                error_log("Не удалось отправить чек на email: $email");
            }
            // --- Конец: Отправка чека на email ---

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"  rel="stylesheet" />
    <script src="https://api-maps.yandex.ru/2.1/?apikey=ваш_ключ&lang=ru_RU" type="text/javascript"></script>
    <style>
        #map { height: 400px; margin-bottom: 15px; } 
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="container mt-4" style="max-width: 600px;">
    <h1 class="mb-4">Оформление заказа</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Спасибо! Ваш заказ успешно оформлен.<br>
            Номер вашего заказа: <strong><?=$orderNumber?></strong>
        </div>
        <a href="/pages/home.php" class="btn btn-primary">Вернуться к покупкам</a>
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
            <div class="row mb-3">
                <div class="col">
                    <label for="first_name" class="form-label">Имя</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" required value="<?=htmlspecialchars($_POST['first_name'] ?? $userData['first_name'] ?? '')?>">
                </div>
                <div class="col">
                    <label for="last_name" class="form-label">Фамилия</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" required value="<?=htmlspecialchars($_POST['last_name'] ?? $userData['last_name'] ?? '')?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?=htmlspecialchars($_POST['email'] ?? $userData['email'] ?? '')?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Выберите адрес доставки на карте</label>
                <div id="map"></div>
                <input type="hidden" id="delivery_lat" name="delivery_lat" required value="<?=htmlspecialchars($_POST['delivery_lat'] ?? $userData['delivery_lat'] ?? '')?>">
                <input type="hidden" id="delivery_lon" name="delivery_lon" required value="<?=htmlspecialchars($_POST['delivery_lon'] ?? $userData['delivery_lon'] ?? '')?>">
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

<!-- Инициализация карты -->
<script type="text/javascript">
    ymaps.ready(init);
    let myMap, myPlacemark;

    function init() {
        const lat = <?=json_encode($userData['delivery_lat'] ?? 55.751574)?>;
        const lon = <?=json_encode($userData['delivery_lon'] ?? 37.573856)?>;

        myMap = new ymaps.Map("map", {
            center: [lat, lon],
            zoom: 12
        });

        myPlacemark = new ymaps.Placemark([lat, lon], {}, {draggable: true});
        myMap.geoObjects.add(myPlacemark);

        myPlacemark.events.add('dragend', function () {
            const coords = myPlacemark.geometry.getCoordinates();
            document.getElementById('delivery_lat').value = coords[0];
            document.getElementById('delivery_lon').value = coords[1];
        });

        myMap.events.add('click', function (e) {
            const coords = e.get('coords');
            myPlacemark.geometry.setCoordinates(coords);
            document.getElementById('delivery_lat').value = coords[0];
            document.getElementById('delivery_lon').value = coords[1];
        });
    }
</script>

</body>
</html>