<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/ui.php';

require_login();

$search = trim((string) ($_GET['search'] ?? ''));
$productFilter = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
$warehouseFilter = filter_input(INPUT_GET, 'warehouse_id', FILTER_VALIDATE_INT);
$productFilter = $productFilter !== false && $productFilter !== null && $productFilter > 0 ? $productFilter : 0;
$warehouseFilter = $warehouseFilter !== false && $warehouseFilter !== null && $warehouseFilter > 0 ? $warehouseFilter : 0;

$productStatement = $pdo->prepare('SELECT product_id, product_name FROM Product ORDER BY product_name');
$productStatement->execute();
$products = $productStatement->fetchAll();

$warehouseStatement = $pdo->prepare('SELECT warehouse_id, warehouse_name FROM Warehouse ORDER BY warehouse_name');
$warehouseStatement->execute();
$warehouses = $warehouseStatement->fetchAll();

$inventoryStatement = $pdo->prepare('SELECT i.inventory_id, p.product_name, w.warehouse_name, i.quantity, p.reorder_level, p.unit_price, i.last_updated FROM Inventory AS i JOIN Product AS p ON p.product_id = i.product_id JOIN Warehouse AS w ON w.warehouse_id = i.warehouse_id WHERE (? = \'\' OR p.product_name LIKE ?) AND (? = 0 OR p.product_id = ?) AND (? = 0 OR w.warehouse_id = ?) ORDER BY p.product_name, w.warehouse_name');
$inventoryStatement->execute([$search, '%' . $search . '%', $productFilter, $productFilter, $warehouseFilter, $warehouseFilter]);
$inventory = $inventoryStatement->fetchAll();

$totalValue = 0.0;
$totalUnits = 0;
$lowCount = 0;
foreach ($inventory as $row) {
    $totalValue += (float) $row['unit_price'] * (int) $row['quantity'];
    $totalUnits += (int) $row['quantity'];
    if ((int) $row['quantity'] <= (int) $row['reorder_level']) {
        $lowCount++;
    }
}

$page_title = 'Inventory | Inventory Manager';
require __DIR__ . '/includes/header.php';

page_header('Current Inventory', 'Search stock quantities by product and warehouse.');
?>
<section class="card">
    <div class="card__head">
        <h2 class="card__title"><?= icon('search', 16) ?>Filters</h2>
        <a class="button button--ghost button--sm" href="inventory.php"><?= icon('close', 15) ?>Clear</a>
    </div>
    <form class="toolbar" method="get">
        <div class="form-group">
            <label for="search">Product Search</label>
            <input id="search" name="search" value="<?= e($search) ?>" placeholder="Product name">
        </div>
        <div class="form-group">
            <label for="product_id">Product</label>
            <select id="product_id" name="product_id">
                <option value="">All products</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= e((string) $product['product_id']) ?>" <?= $productFilter === (int) $product['product_id'] ? 'selected' : '' ?>><?= e($product['product_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="warehouse_id">Warehouse</label>
            <select id="warehouse_id" name="warehouse_id">
                <option value="">All warehouses</option>
                <?php foreach ($warehouses as $warehouse): ?>
                    <option value="<?= e((string) $warehouse['warehouse_id']) ?>" <?= $warehouseFilter === (int) $warehouse['warehouse_id'] ? 'selected' : '' ?>><?= e($warehouse['warehouse_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="button" type="submit"><?= icon('filter', 16) ?>Apply Filters</button>
    </form>
</section>

<section class="card">
    <div class="card__head">
        <h2 class="card__title"><?= icon('inventory', 16) ?>Stock Levels</h2>
        <div class="badge badge--low"><?= e((string) $lowCount) ?> low</div>
    </div>

    <?php if ($inventory === []): ?>
        <?= empty_state('No inventory rows match these filters.', 'inventory') ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Warehouse</th>
                        <th class="num">Quantity</th>
                        <th class="num">Reorder</th>
                        <th>Status</th>
                        <th class="num">Unit Price</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory as $item): ?>
                        <?php
                        $qty = (int) $item['quantity'];
                        $reorder = (int) $item['reorder_level'];
                        $isLow = $qty <= $reorder;
                        ?>
                        <tr>
                            <td><span class="cell-title" title="<?= e($item['product_name']) ?>"><?= e($item['product_name']) ?></span></td>
                            <td><?= e($item['warehouse_name']) ?></td>
                            <td class="num"><?= e((string) $qty) ?></td>
                            <td class="num"><?= e((string) $reorder) ?></td>
                            <td><?= $isLow ? '<span class="badge badge--low">' . icon('alert', 13) . 'Low</span>' : '<span class="badge badge--ok">' . icon('check', 13) . 'OK</span>' ?></td>
                            <td class="num">₹<?= e(number_format((float) $item['unit_price'], 2)) ?></td>
                            <td><?= e($item['last_updated']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-foot">
            <span><?= e((string) count($inventory)) ?> row<?= count($inventory) === 1 ? '' : 's' ?></span>
            <span><?= e(number_format($totalUnits)) ?> units · ₹<?= e(number_format($totalValue, 2)) ?></span>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
