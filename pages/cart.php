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
    SELECT p.id, p.name, p.price, p.main_image, c.quantity
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем избранное
$stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
$stmt->execute([$user_id]);
$wishlistItems = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);


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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"  rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"  rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/styles.css" />
    <style>
        .cart-item {
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
        }
        .cart-item-image img {
            max-height: 80px;
            object-fit: contain;
        }
        .quantity-input {
            width: 50px;
            text-align: center;
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="container mt-4">
    <h1 class="text-center mb-4">Корзина</h1>

    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info text-center">Ваша корзина пуста.</div>
    <?php else: ?>

        <!-- Выбор всех товаров -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                <label class="form-check-label" for="selectAllCheckbox">Выбрать все</label>
            </div>
            <div>
                <button class="btn btn-light btn-sm" onclick="removeSelected()"><i class="bi bi-trash"></i></button>
            </div>
        </div>

        <!-- Список товаров -->
        <?php foreach ($cartItems as $item): ?>
            <div class="cart-item d-flex align-items-start mb-3" data-product-id="<?= $item['id'] ?>">
                <div class="cart-item-image me-3">
                    <img src="<?= htmlspecialchars($item['main_image'] ?? '/assets/images/no-image.jpg') ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="img-fluid rounded" style="max-width: 80px;">
                </div>
                <div class="cart-item-details flex-grow-1">
                    <div class="form-check float-end">
                        <input class="form-check-input cart-item-checkbox" type="checkbox">
                    </div>
                    <h5 class="mb-0"><?= htmlspecialchars($item['name']) ?></h5>
                    <p class="mb-1"><?= number_format($item['price'], 2, ',', ' ') ?> ₽</p>
                    <div class="d-flex align-items-center mb-2">
                        <button class="btn btn-outline-secondary btn-sm me-2" onclick="updateQuantity(this, -1, <?= $item['id'] ?>)">-</button>
                        <input type="number" class="form-control form-control-sm quantity-input text-center" value="<?= $item['quantity'] ?>" min="1" style="width: 50px;" readonly>
                        <button class="btn btn-outline-secondary btn-sm ms-2" onclick="updateQuantity(this, 1, <?= $item['id'] ?>)">+</button>
                    </div>
                    <div class="d-flex align-items-center">
                        <?php
                        $heartClass = in_array($item['id'], $wishlistItems) ? 'bi-heart-fill text-danger' : 'bi-heart';
                        ?>
                        <a href="#" class="btn btn-light btn-sm me-2" onclick="moveToWishlist(this, <?= $item['id'] ?>)">
                            <i class="bi <?= $heartClass ?>"></i>
                        </a>
                        <a href="#" class="btn btn-light btn-sm me-2" onclick="removeItem(this, <?= $item['id'] ?>)"><i class="bi bi-trash"></i></a>
                        <a href="#" class="btn btn-primary btn-sm">Купить</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Итоговая сумма -->
        <div class="mt-4">
            <h5>Итого:</h5>
            <p id="totalAmount" class="fs-5 fw-bold"><?= number_format($total, 2, ',', ' ') ?> ₽</p>
        </div>

        <button class="btn btn-success w-100 mt-3" onclick="window.location.href='checkout.php'">Перейти к оформлению</button>

    <?php endif; ?>
</main>

<!-- Модальное окно подтверждения -->
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmActionLabel">Подтвердите действие</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
      </div>
      <div class="modal-body">
        Вы действительно хотите удалить этот товар из корзины?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Удалить</button>
      </div>
    </div>
  </div>
</div>

<script>
    let currentProductId = null;
    let currentItemElement = null;

    function updateQuantity(button, delta, productId) {
        const input = button.parentElement.querySelector('.quantity-input');
        let currentQuantity = parseInt(input.value);
        let newQuantity = currentQuantity + delta;

        if (newQuantity < 1) {
            newQuantity = 1;
        }

        input.value = newQuantity;

        fetch('../api/cart_update_quantity.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}&quantity=${newQuantity}`
        }).then(response => response.json())
          .then(data => {
              if (!data.success) {
                  alert('Ошибка при обновлении количества');
              } else {
                  updateTotal(); // Обновляем сумму
              }
          });
    }

    function removeItem(button, productId) {
        currentProductId = productId;
        currentItemElement = button.closest('.cart-item');

        const confirmModal = new bootstrap.Modal(document.getElementById('confirmActionModal'));
        confirmModal.show();
    }

    function moveToWishlist(button, productId) {
        fetch('../api/cart_move_to_wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Найдём <i> внутри кнопки
                const icon = button.querySelector('i');

                if (icon) {
                    // Меняем иконку на заполненное сердечко
                    icon.classList.remove('bi-heart');
                    icon.classList.add('bi-heart-fill', 'text-danger'); // красный цвет
                }

                // Можно запретить повторное нажатие (чтобы не добавлять повторно)
                button.onclick = null;
            } else {
                alert(data.message || 'Ошибка при перемещении в избранное');
            }
        })
        .catch(err => {
            console.error('Ошибка при запросе:', err);
            alert('Произошла ошибка при отправке запроса');
        });
    }



    function removeSelected() {
        const items = document.querySelectorAll('.cart-item');
        items.forEach(item => {
            const checkbox = item.querySelector('.cart-item-checkbox');
            if (checkbox && checkbox.checked) {
                const productId = item.dataset.productId;

                fetch('../api/cart_remove_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        item.remove();
                        updateTotal();
                    } else {
                        alert('Не удалось удалить товар: ' + (data.message || 'неизвестная ошибка'));
                    }
                });
            }
        });
    }

    function updateTotal() {
        let total = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            const priceText = item.querySelector('p.mb-1').textContent.trim().replace(/\s+/g, '').replace('₽', '');
            const quantity = parseInt(item.querySelector('.quantity-input').value);
            const price = parseFloat(priceText);

            if (!isNaN(price) && !isNaN(quantity)) {
                total += price * quantity;
            }
        });

        const formattedTotal = total.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('totalAmount').textContent = formattedTotal + ' ₽';
    }

    document.addEventListener("DOMContentLoaded", function () {
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                const checkboxes = document.querySelectorAll('.cart-item-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        }

        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function () {
                if (!currentProductId || !currentItemElement) return;

                fetch('../api/cart_remove_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `product_id=${currentProductId}`
                }).then(response => response.json())
                  .then(data => {
                      const modalEl = document.getElementById('confirmActionModal');
                      if (modalEl) {
                          const modal = bootstrap.Modal.getInstance(modalEl);
                          if (modal) modal.hide();
                      }

                      if (data.success) {
                          currentItemElement.remove();
                          updateTotal();
                      } else {
                          alert('Ошибка при удалении товара');
                      }

                      currentProductId = null;
                      currentItemElement = null;
                  });
            });
        }
    });
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> 
</body>
</html>