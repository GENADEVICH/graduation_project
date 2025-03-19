<?php
// includes/db.php

$host = '127.0.0.1'; 
$dbname = 'alexis222w_marke'; 
$username = 'alexis222w_marke';
$password = 'LKZSMVG*LZB9MrUP';
$port = '3308'; // 

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>