<!-- pages/partials/filters.php -->

<form method="GET" action="" id="filter-form">
    <input type="hidden" name="id" value="<?= $category['id'] ?>" />

    <!-- Фильтр по бренду -->
    <div class="mb-3">
        <h6>Бренд</h6>
        <?php foreach ($brands as $brand): ?>
            <div class="form-check">
                <input 
                    class="form-check-input" 
                    type="checkbox" 
                    name="brand[]" 
                    value="<?= $brand['brand_id'] ?>"
                    id="brand_<?= $brand['brand_id'] ?>"
                    <?= in_array((string)$brand['brand_id'], $_GET['brand'] ?? [], true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="brand_<?= $brand['brand_id'] ?>">
                    <?= htmlspecialchars($brand['brand_name']) ?>
                </label>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Фильтр по цене -->
    <div class="mb-3">
        <h6>Цена</h6>
        <div class="input-group mb-2">
            <span class="input-group-text">От</span>
            <input type="number" min="0" class="form-control" name="min_price" value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>">
        </div>
        <div class="input-group">
            <span class="input-group-text">До</span>
            <input type="number" min="0" class="form-control" name="max_price" value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>">
        </div>
    </div>

    <!-- Распродажа -->
    <div class="mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="on_sale" id="on_sale" <?= !empty($_GET['on_sale']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="on_sale">Только со скидкой</label>
        </div>
    </div>

    <!-- Кнопка -->
    <button type="submit" class="btn btn-primary w-100">Применить</button>
</form>