<?php
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}

$brand_id = $_GET['id'] ?? null;

if (!$brand_id || !is_numeric($brand_id)) {
    die("Некорректный ID бренда.");
}

$stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
$stmt->execute([$brand_id]);
$brand = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$brand) {
    die("Бренд не найден.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $logo_url = $brand['logo_url']; // Сохраняем текущий логотип по умолчанию

    if (empty($name)) {
        $error = "Название бренда обязательно.";
    } else {
        // Обработка загрузки нового логотипа
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/brands/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($ext, $allowedExtensions)) {
                $error = "Запрещённый формат изображения.";
            } else {
                $filename = uniqid('brand_', true) . '.' . $ext;
                $destination = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
                    // Удаляем старое изображение, если оно было
                    if ($brand['logo_url'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $brand['logo_url'])) {
                        unlink($_SERVER['DOCUMENT_ROOT'] . $brand['logo_url']);
                    }
                    $logo_url = '/uploads/brands/' . $filename;
                } else {
                    $error = "Ошибка при сохранении логотипа.";
                }
            }
        }

        if (!$error) {
            try {
                $stmt = $pdo->prepare("UPDATE brands SET name = ?, logo_url = ? WHERE id = ?");
                $stmt->execute([$name, $logo_url, $brand_id]);
                $success = "Бренд успешно обновлён.";
                header("Location: brands_list.php");
                exit;
            } catch (PDOException $e) {
                $error = "Ошибка при сохранении: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать бренд</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"  rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Редактировать бренд</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="name" class="form-label">Название бренда</label>
            <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($brand['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="logo" class="form-label">Текущий логотип</label>
            <?php if ($brand['logo_url']): ?>
                <div class="mb-2">
                    <img src="<?= htmlspecialchars($brand['logo_url']) ?>" alt="Логотип" width="100" class="img-thumbnail">
                </div>
            <?php endif; ?>
            <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        <a href="/admin/brands/brands_list.php" class="btn btn-secondary">Отмена</a>
    </form>
</div>
</body>
</html>