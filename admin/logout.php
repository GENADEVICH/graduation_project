<?php
// admin/logout.php

session_start();
session_destroy(); // Уничтожаем сессию
redirect('/admin/login.php'); // Перенаправляем на страницу входа
?>