<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$errors = [];
$success = '';

$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Пользователь';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($subject === '') {
        $errors['subject'] = 'Тема сообщения обязательна.';
    }
    if ($message === '') {
        $errors['message'] = 'Сообщение не может быть пустым.';
    }

    if (empty($errors)) {
        // Формируем тело письма в HTML
        $email_body = "<p><strong>Пользователь:</strong> {$username} (ID: {$user_id})</p>";
        $email_body .= "<p><strong>Тема:</strong> " . htmlspecialchars($subject) . "</p>";
        $email_body .= "<p><strong>Сообщение:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";

        // Отправляем письмо на техподдержку
        $support_email = 'support@akrapov1c.ru'; // меняй на свой адрес
        $mail_sent = send_email($support_email, "Сообщение от пользователя {$username}: {$subject}", $email_body);

        if ($mail_sent) {
            $success = 'Ваше сообщение успешно отправлено. Мы свяжемся с вами в ближайшее время.';
        } else {
            $errors['general'] = 'Ошибка при отправке сообщения. Попробуйте позже.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Обращение в техподдержку</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container mt-4" style="max-width: 600px;">
    <h1 class="mb-4">Обращение в техническую поддержку</h1>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <a href="/pages/profile.php" class="btn btn-primary">Вернуться в профиль</a>
    <?php else: ?>
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label for="subject" class="form-label">Тема</label>
                <input type="text" 
                       class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>" 
                       id="subject" 
                       name="subject" 
                       value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" 
                       required>
                <div class="invalid-feedback">
                    <?= $errors['subject'] ?? '' ?>
                </div>
            </div>

            <div class="mb-3">
                <label for="message" class="form-label">Сообщение</label>
                <textarea class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>" 
                          id="message" 
                          name="message" 
                          rows="6" 
                          required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                <div class="invalid-feedback">
                    <?= $errors['message'] ?? '' ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send"></i> Отправить
            </button>
            <a href="/pages/profile.php" class="btn btn-secondary ms-2">Отмена</a>
        </form>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
