<?php
// become_seller.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require '../includes/db.php';
require '../includes/functions.php';

// Проверка авторизации
if (!isLoggedIn()) {
    redirect('/pages/login.php');
}

$errors = [];
$success = '';

// Обработка POST-запроса при отправке формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $store_name = trim($_POST['store_name'] ?? '');
    $country = $_POST['country'] ?? '';
    $business_form = $_POST['business_form'] ?? '';
    $inn = $_POST['inn'] ?? '';
    $activity_field = $_POST['activity_field'] ?? '';
    $email = $_POST['email'] ?? '';

    // Валидация данных
    if (empty($store_name)) {
        $errors[] = 'Введите имя магазина.';
    }
    if (empty($country)) {
        $errors[] = 'Выберите страну регистрации.';
    }
    if (empty($business_form)) {
        $errors[] = 'Выберите форму организации бизнеса.';
    }
    if (empty($inn)) {
        $errors[] = 'Введите ИНН.';
    } elseif (!preg_match('/^\d{10,12}$/', $inn)) {
        $errors[] = 'ИНН должен состоять из 10 или 12 цифр.';
    }
    if (empty($activity_field)) {
        $errors[] = 'Выберите сферу деятельности.';
    }
    if (empty($email)) {
        $errors[] = 'Введите email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный формат email.';
    }

    // Если нет ошибок, сохраняем данные в БД
    if (empty($errors)) {
        try {
            // Подготовленный запрос для вставки данных
            $stmt = $pdo->prepare("INSERT INTO sellers (user_id, store_name, country, business_form, inn, activity_field, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                $store_name,
                $country,
                $business_form,
                $inn,
                $activity_field,
                $email
            ]);

            // Успешное сохранение
            $success = 'Заявление успешно отправлено на проверку.';
        } catch (PDOException $e) {
            $errors[] = 'Ошибка при сохранении данных: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Стать продавцом</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/styles.css" />
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container mt-4">
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h2 class="card-title">Пройдите регистрацию</h2>
            <p class="card-text">Заполните информацию для регистрации как продавца.</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/pages/become_seller.php">
                <!-- Имя магазина -->
                <div class="mb-3">
                    <label for="store_name" class="form-label">Имя магазина</label>
                    <input type="text" id="store_name" name="store_name" class="form-control" placeholder="Введите имя магазина" required value="<?= isset($store_name) ? htmlspecialchars($store_name) : '' ?>">
                </div>

                <!-- Страна регистрации -->
                <div class="mb-3">
                    <label for="country" class="form-label">Страна регистрации</label>
                    <select id="country" name="country" class="form-select" required>
                        <option value="">Выберите страну</option>
                        <option value="Россия" <?= (isset($country) && $country === 'Россия') ? 'selected' : '' ?>>Россия</option>
                        <option value="Беларусь" <?= (isset($country) && $country === 'Беларусь') ? 'selected' : '' ?>>Беларусь</option>
                        <option value="Казахстан" <?= (isset($country) && $country === 'Казахстан') ? 'selected' : '' ?>>Казахстан</option>
                    </select>
                </div>

                <!-- Форма организации бизнеса -->
                <div class="mb-3">
                    <label for="business_form" class="form-label">Форма организации бизнеса</label>
                    <select id="business_form" name="business_form" class="form-select" required>
                        <option value="">Выберите форму</option>
                        <option value="ИП" <?= (isset($business_form) && $business_form === 'ИП') ? 'selected' : '' ?>>Индивидуальный предприниматель (ИП)</option>
                        <option value="ООО" <?= (isset($business_form) && $business_form === 'ООО') ? 'selected' : '' ?>>Общество с ограниченной ответственностью (ООО)</option>
                        <option value="УСН" <?= (isset($business_form) && $business_form === 'УСН') ? 'selected' : '' ?>>Упрощённая система налогообложения (УСН)</option>
                    </select>
                </div>

                <!-- ИНН -->
                <div class="mb-3">
                    <label for="inn" class="form-label">ИНН</label>
                    <input type="text" id="inn" name="inn" class="form-control" placeholder="Введите ИНН" 
                        maxlength="12" pattern="\d{10,12}" required
                        oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                        value="<?= isset($inn) ? htmlspecialchars($inn) : '' ?>">
                    <small class="text-muted">ИНН должен состоять из 10 или 12 цифр.</small>
                </div>

                <!-- Сфера деятельности -->
                <div class="mb-3">
                    <label for="activity_field" class="form-label">Сфера деятельности на Lumi</label>
                    <select id="activity_field" name="activity_field" class="form-select" required>
                        <option value="">Выберите сферу</option>
                        <option value="продавец_товаров" <?= (isset($activity_field) && $activity_field === 'продавец_товаров') ? 'selected' : '' ?>>Продавец товаров</option>
                        <option value="представитель_бренда" <?= (isset($activity_field) && $activity_field === 'представитель_бренда') ? 'selected' : '' ?>>Представитель бренда</option>
                    </select>
                </div>

                <!-- Email для уведомлений -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email для уведомлений</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Введите email" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
                </div>

                <!-- Кнопки -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Отправить</button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
