<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$user_id = $_SESSION['user_id'];

// Обработка добавления товара в корзину
if (isset($_GET['action']) && $_GET['action'] === 'add' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    // Проверяем, есть ли товар в корзине
    $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Если есть — увеличиваем количество на 1
        $newQuantity = $existing['quantity'] + 1;
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$newQuantity, $user_id, $product_id]);
    } else {
        // Если нет — добавляем товар с количеством 1
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $stmt->execute([$user_id, $product_id]);
    }

    redirect('/pages/cart.php');
}

// Обработка удаления товара из корзины
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    redirect('/pages/cart.php');
}

// Очистка корзины
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    redirect('/pages/cart.php');
}

// Получаем содержимое корзины
$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.price, p.main_image, c.quantity
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Подсчет общей суммы
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Корзина</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
<link rel="stylesheet" href="/assets/css/styles.css" />
<style>
  td, th { vertical-align: middle !important; }
  .cart-img { max-width: 60px; max-height: 60px; object-fit: contain; }
  .cursor-pointer { cursor: pointer; }
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="container mt-4">
  <h1 class="text-center mb-4">Корзина</h1>

  <?php if (empty($cartItems)): ?>
    <div class="alert alert-info text-center">Ваша корзина пуста.</div>
  <?php else: ?>
    <table class="table table-bordered align-middle text-center">
      <thead class="table-dark">
        <tr>
          <th>Изображение</th>
          <th>Наименование</th>
          <th>Цена</th>
          <th>Количество</th>
          <th>Сумма</th>
          <th>Действия</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cartItems as $item): ?>
          <tr data-product-id="<?= $item['id'] ?>">
            <td>
              <?php if (!empty($item['main_image'])): ?>
                <img src="<?= htmlspecialchars($item['main_image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-img rounded" />
              <?php else: ?>
                <img src="/assets/images/no-image.jpg" alt="Нет изображения" class="cart-img rounded" />
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= number_format($item['price'], 2, ',', ' ') ?> руб.</td>
            <td>
              <input type="number" class="form-control quantity-input" value="<?= $item['quantity'] ?>" min="1" style="width: 80px;" />
            </td>
            <td class="item-total"><?= number_format($item['price'] * $item['quantity'], 2, ',', ' ') ?> руб.</td>
            <td>
              <a href="/pages/cart.php?action=remove&id=<?= $item['id'] ?>" class="btn btn-danger btn-sm" title="Удалить товар">
                <i class="bi bi-trash"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-center">
      <h3>Общая сумма: <span id="cart-total"><?= number_format($total, 2, ',', ' ') ?></span> руб.</h3>
      <div>
        <a href="/pages/cart.php?action=clear" class="btn btn-warning me-2" onclick="return confirm('Очистить всю корзину?')">
          <i class="bi bi-trash"></i> Очистить корзину
        </a>
        <a href="/pages/checkout.php" class="btn btn-primary">
          <i class="bi bi-credit-card"></i> Оформить заказ
        </a>
      </div>
    </div>
  <?php endif; ?>
</main>

<script>
document.querySelectorAll('.quantity-input').forEach(input => {
  input.addEventListener('change', function() {
    let tr = this.closest('tr');
    let productId = tr.getAttribute('data-product-id');
    let quantity = this.value;

    if (quantity < 1) {
      alert('Количество не может быть меньше 1');
      this.value = 1;
      quantity = 1;
    }

    fetch('/api/cart_update.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ product_id: productId, quantity: quantity })
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        alert('Ошибка: ' + data.error);
      } else {
        // Обновляем сумму для товара
        tr.querySelector('.item-total').textContent = data.itemTotal.toFixed(2).replace('.', ',') + ' руб.';
        // Обновляем общую сумму корзины
        document.getElementById('cart-total').textContent = Number(data.total).toFixed(2).replace('.', ',');
      }
    })
    .catch(() => alert('Ошибка при обновлении корзины'));
  });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
