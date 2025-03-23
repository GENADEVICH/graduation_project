<?php
// pages/error.php

http_response_code(404); // Устанавливаем HTTP-статус 404
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница не найдена</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5 text-center">
        <h1>Ошибка 404</h1>
        <p>Запрошенная страница не найдена.</p>
        <a href="/" class="btn btn-primary">Вернуться на главную</a>
    </div>
</body>
</html>