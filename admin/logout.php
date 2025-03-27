<?php
// logout.php

session_start(); // Запускаем сессию
session_destroy(); // Уничтожаем сессию
header("Location: /admin/login.php"); // Перенаправляем на страницу входа
exit;
?>