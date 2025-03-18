<?php
// includes/header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Запускаем сессию, если она еще не запущена
}
?>
<header>
    <h1>Маркетплейс</h1>
    <nav>
        <a href="/pages/home.php">Главная</a>
        <?php if (isLoggedIn()): ?>
            <a href="/pages/profile.php">Профиль</a>
            <a href="/pages/wishlist.php">Избранное</a>
        <?php else: ?>
            <a href="/pages/login.php">Войти</a>
        <?php endif; ?>
        <a href="/pages/cart.php" class="cart-link">
            Корзина
            <?php if (!empty($_SESSION['cart'])): ?>
                <span class="cart-count"><?= count($_SESSION['cart']) ?></span>
            <?php endif; ?>
        </a>
    </nav>
</header>