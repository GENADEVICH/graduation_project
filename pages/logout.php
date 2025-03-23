<?php
// pages/logout.php

session_start();
require '../includes/functions.php'; // Подключение функций

logout(); // Вызываем функцию выхода
?>