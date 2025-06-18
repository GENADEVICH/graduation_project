<?php
require __DIR__ . '/../../includes/db.php'; // Путь нужно проверить

header('Content-Type: text/html');

if (!isset($_GET['category_id']) || !is_numeric($_GET['category_id'])) {
    echo '';
    exit;
}

$category_id = (int)$_GET['category_id'];

$stmt = $pdo->prepare("SELECT * FROM characteristics WHERE category_id = ? ORDER BY name");
$stmt->execute([$category_id]);
$characteristics = $stmt->fetchAll();

if (!empty($characteristics)): ?>
    <h3>Характеристики</h3>
    <?php foreach ($characteristics as $ch): ?>
        <div class="mb-3">
            <label for="char_<?= $ch['id'] ?>" class="form-label"><?= htmlspecialchars($ch['name']) ?></label>
            <?php 
                switch ($ch['value_type']) {
                    case 'string':
                        echo '<input type="text" name="characteristics[' . $ch['id'] . ']" id="char_' . $ch['id'] . '" class="form-control">';
                        break;
                    case 'integer':
                        echo '<input type="number" name="characteristics[' . $ch['id'] . ']" id="char_' . $ch['id'] . '" class="form-control">';
                        break;
                    case 'decimal':
                        echo '<input type="number" step="0.01" name="characteristics[' . $ch['id'] . ']" id="char_' . $ch['id'] . '" class="form-control">';
                        break;
                    case 'boolean':
                        echo '<input type="checkbox" name="characteristics[' . $ch['id'] . ']" id="char_' . $ch['id'] . '" value="1">';
                        break;
                }
            ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="alert alert-info">Для выбранной категории нет характеристик</div>
<?php endif; ?>