<?php
// pages/error.php

http_response_code(404); // Устанавливаем HTTP-статус 404
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Страница не найдена</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container d-flex flex-column justify-content-center align-items-center vh-100 text-center">
        <h1 class="display-1 text-danger">404</h1>
        <h2 class="mb-3">Страница не найдена</h2>
        <p class="lead mb-4">Возможно, вы ошиблись в адресе или эта страница была удалена.</p>
        <a href="/" class="btn btn-primary btn-lg">Вернуться на главную</a>
    </div>
</body>
</html>
