<?php
// includes/db.php

$host = '0.0.0.0'; 
$dbname = '   '; 
$username = '  ';
$password = '  ';
$port = '  '; // 

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>