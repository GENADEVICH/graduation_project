<?php
session_start();
require '../../includes/db.php';

// Проверка авторизации как администратор
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}

$error = '';
$success = '';

// Обработка добавления нового бренда
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $brand_name = trim($_POST['name'] ?? '');
    $logo_url = null;

    if ($brand_name === '') {
        $error = "Название бренда не может быть пустым.";
    } else {
        // Обработка загрузки логотипа
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/brands/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($ext, $allowedExtensions)) {
                $error = "Запрещённый формат изображения. Разрешены JPG, JPEG, PNG, GIF.";
            } else {
                $filename = uniqid('brand_', true) . '.' . $ext;
                $destination = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
                    $logo_url = '/uploads/brands/' . $filename;
                } else {
                    $error = "Ошибка при сохранении логотипа.";
                }
            }
        }

        if (!$error) {
            try {
                $stmt = $pdo->prepare("INSERT INTO brands (name, logo_url) VALUES (?, ?)");
                $stmt->execute([$brand_name, $logo_url]);
                $success = "Бренд успешно добавлен.";
                header("Location: brands_list.php");
                exit;
            } catch (PDOException $e) {
                $error = "Ошибка при добавлении бренда: " . $e->getMessage();
            }
        }
    }
}

// Обработка удаления бренда
if (isset($_GET['delete'])) {
    $brand_id = $_GET['delete'];

    try {
        // Проверим, есть ли товары у этого бренда
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?");
        $stmt->execute([$brand_id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error_message'] = "Нельзя удалить бренд, так как он используется в товарах.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
            $stmt->execute([$brand_id]);
            $_SESSION['success_message'] = "Бренд успешно удален.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Ошибка при удалении бренда.";
    }

    header("Location: brands_list.php");
    exit;
}

// Получаем все бренды
try {
    $stmt = $pdo->query("SELECT id, name, logo_url FROM brands ORDER BY name ASC");
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при загрузке брендов: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список брендов</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"  rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"  rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fs-4" href="/admin/dashboard.php">
            <i class="bi bi-speedometer2 me-2"></i>Панель администратора
        </a>
        <a href="/pages/profile.php" class="btn btn-outline-danger">
            <i class="bi bi-box-arrow-right"></i> Выйти
        </a>
    </div>
</nav>

<div class="container">
    <h1 class="mb-4">Бренды товаров</h1>

    <!-- Сообщения об успехе или ошибках -->
    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Форма добавления нового бренда -->
    <div class="card mb-4">
        <div class="card-header">
            Добавить бренд
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="name" class="form-label">Название бренда</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="logo" class="form-label">Логотип (необязательно)</label>
                    <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
                </div>
                <button type="submit" name="add_brand" class="btn btn-success">Добавить бренд</button>
            </form>
        </div>
    </div>

    <!-- Список брендов -->
    <h4>Существующие бренды</h4>
    <?php if (empty($brands)): ?>
        <p>Нет добавленных брендов.</p>
    <?php else: ?>
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($brands as $brand): ?>
                    <tr>
                        <td><?= htmlspecialchars($brand['id']) ?></td>
                        <td><?= htmlspecialchars($brand['name']) ?></td>
                        <td class="text-center">
                            <a href="/admin/brands/edit_brand.php?id=<?= $brand['id'] ?>" class="btn btn-sm btn-outline-primary me-2">
                                <i class="bi bi-pencil"></i> Редактировать
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $brand['id'] ?>">
                                <i class="bi bi-trash"></i> Удалить
                            </a>
                        </td>
                    </tr>

                    <!-- Modal для удаления -->
                    <div class="modal fade" id="deleteModal<?= $brand['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteModalLabel">Подтверждение удаления</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Вы действительно хотите удалить бренд "<strong><?= htmlspecialchars($brand['name']) ?></strong>"?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                    <a href="?delete=<?= $brand['id'] ?>" class="btn btn-danger">Удалить</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> 
</body>
</html>