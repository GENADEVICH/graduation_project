<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Валидация
    if (empty($email)) {
        $errors[] = "Email обязателен.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат email.";
    }

    if (empty($errors)) {
        // Проверяем, существует ли пользователь
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = "Пользователь с таким email не найден.";
        } else {
            // Генерируем токен
            $token = bin2hex(random_bytes(50));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Сохраняем токен
            $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)")
               ->execute([$email, $token, $expires]);

            // Отправляем ссылку на сброс
            $resetLink = "http://".$_SERVER['HTTP_HOST']."/pages/reset-password.php?token=$token";
            $subject = "Восстановление пароля";

            // Красивое HTML-письмо
            $message = '
            <!DOCTYPE html>
            <html lang="ru">
            <head>
                <meta charset="UTF-8">
                <title>Восстановление пароля</title>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                    .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); padding: 30px; }
                    h2 { color: #333333; }
                    p { font-size: 16px; color: #555555; }
                    .btn { display: inline-block; margin-top: 20px; padding: 12px 25px; font-size: 16px; color: #fff; background-color: #0d6efd; text-decoration: none; border-radius: 5px; }
                    .footer { margin-top: 30px; font-size: 14px; color: #999999; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h2>Восстановление пароля</h2>
                    <p>Мы получили запрос на восстановление пароля для вашей учетной записи. Нажмите на кнопку ниже, чтобы сбросить пароль:</p>
                    <a href="'.$resetLink.'" class="btn">Сбросить пароль</a>
                    <p class="footer">Если вы не запрашивали сброс пароля, проигнорируйте это письмо.</p>
                </div>
            </body>
            </html>
            ';

            if (send_email($email, $subject, $message)) {
                $success = true;
            } else {
                $errors[] = "Ошибка при отправке письма.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Забыли пароль?</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="mb-4 text-center">Восстановление пароля</h2>

            <?php if ($success): ?>
                <div class="alert alert-success">На ваш email отправлена ссылка для восстановления пароля.</div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><?= implode('<br>', $errors) ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Введите ваш email</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($email ?? '') ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Отправить ссылку</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>