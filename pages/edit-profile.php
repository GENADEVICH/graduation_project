<?php
session_start();
require '../includes/db.php'; // Подключение к базе данных
require '../includes/functions.php'; // Подключение функций

// Проверка, авторизован ли пользователь
if (!isLoggedIn()) {
    redirect('/pages/login.php'); // Перенаправляем неавторизованных пользователей
}

// Получаем данные текущего пользователя
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, profile_picture FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Пользователь не найден.");
}

$errors = [];
$success = '';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $profile_picture = $user['profile_picture']; // Оставляем старое фото по умолчанию

    // Обработка загрузки фото
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $file_name = $_FILES['profile_picture']['name'];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        // Проверка расширения файла
        if (in_array(strtolower($file_ext), $allowed_ext)) {
            // Генерация нового имени файла
            $new_file_name = 'profile_' . $user_id . '.' . $file_ext;
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/profile_images/';
            $upload_path = $upload_dir . $new_file_name;

            // Загружаем файл
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $profile_picture = '/uploads/profile_images/' . $new_file_name;
            } else {
                $errors['profile_picture'] = 'Ошибка при загрузке изображения.';
            }
        } else {
            $errors['profile_picture'] = 'Неправильный формат изображения. Разрешены jpg, jpeg, png, gif.';
        }
    }

    // Проверка на ошибки
    if (empty($errors)) {
        // Обновляем данные пользователя в базе
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_picture = ? WHERE id = ?");
        $stmt->execute([$username, $email, $profile_picture, $user_id]);

        $success = 'Профиль успешно обновлен!';
        // Обновляем информацию о пользователе в сессии
        $_SESSION['user_username'] = $username;

        // Если было новое фото, обновляем сессию для корректного отображения
        if ($profile_picture != $user['profile_picture']) {
            $_SESSION['user_profile_picture'] = $profile_picture;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать профиль</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Твои собственные стили -->
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h1 class="card-title mb-4 text-center">Редактировать профиль</h1>

                        <!-- Сообщения об ошибках и успехах -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p><?= htmlspecialchars($error) ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>

                        <!-- Форма редактирования профиля -->
                        <form action="" method="POST" enctype="multipart/form-data">
                            <!-- Фото профиля -->
                            <div class="mb-3 text-center">
                                <label for="profile_picture" class="form-label">Фото профиля</label><br>
                                <?php if (!empty($user['profile_picture']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user['profile_picture'])): ?>
                                    <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Фото пользователя" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="/assets/images/default_profile.png" alt="Заглушка фото пользователя" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="mt-2">
                                    <input type="file" class="form-control" name="profile_picture" id="profile_picture">
                                </div>
                            </div>

                            <!-- Имя пользователя -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Имя пользователя</label>
                                <input type="text" class="form-control" name="username" id="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Сохранить изменения</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS и зависимости -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
