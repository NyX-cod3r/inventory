<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

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

$page_title = 'Inventory | Inventory Manager';
require __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">Current Inventory</h1>
<p class="page-subtitle">Search stock quantities by product and warehouse.</p>
<section class="card">
    <form class="filter-form" method="get">
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
        <button class="button" type="submit">Filter</button>
    </form>
</section>
<section class="card table-card">
    <table class="data-table">
        <thead><tr><th>Product</th><th>Warehouse</th><th>Quantity</th><th>Reorder Level</th><th>Unit Price</th><th>Last Updated</th></tr></thead>
        <tbody>
            <?php foreach ($inventory as $item): ?>
                <tr>
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= e($item['warehouse_name']) ?></td>
                    <td><?= e((string) $item['quantity']) ?></td>
                    <td><?= e((string) $item['reorder_level']) ?></td>
                    <td>₹<?= e(number_format((float) $item['unit_price'], 2)) ?></td>
                    <td><?= e($item['last_updated']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
