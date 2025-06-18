<?php
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

$category_id = $_GET['category_id'] ?? '';
if (!$category_id || !is_numeric($category_id)) {
    echo '<p class="text-muted">Выберите категорию.</p>';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM characteristics WHERE category_id = ? ORDER BY name");
$stmt->execute([$category_id]);
$characteristics = $stmt->fetchAll();

foreach ($characteristics as $ch):
    $val = isset($_POST['characteristics'][$ch['id']]) ? htmlspecialchars($_POST['characteristics'][$ch['id']]) : '';
    echo '<div class="mb-3">';
    echo '<label for="char_'.$ch['id'].'" class="form-label">'.htmlspecialchars($ch['name']).'</label>';
    switch ($ch['value_type']) {
        case 'string':
            echo '<input type="text" name="characteristics['.$ch['id'].']" class="form-control" value="'.$val.'">';
            break;
        case 'integer':
            echo '<input type="number" name="characteristics['.$ch['id'].']" class="form-control" value="'.$val.'">';
            break;
        case 'decimal':
            echo '<input type="number" step="0.01" name="characteristics['.$ch['id'].']" class="form-control" value="'.$val.'">';
            break;
        case 'boolean':
            $checked = $val ? 'checked' : '';
            echo '<input type="checkbox" name="characteristics['.$ch['id'].']" value="1" '.$checked.'>';
            break;
    }
    echo '</div>';
endforeach;
?>