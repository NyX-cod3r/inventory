<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/ui.php';

require_login();

$totalProducts = (int) $pdo->query('SELECT COUNT(*) FROM Product')->fetchColumn();
$totalStockValue = (float) $pdo->query('SELECT COALESCE(SUM(inventory_value), 0) FROM v_inventory_summary')->fetchColumn();
$lowStockCount = (int) $pdo->query('SELECT COUNT(*) FROM v_low_stock')->fetchColumn();
$warehouseCount = (int) $pdo->query('SELECT COUNT(*) FROM Warehouse')->fetchColumn();
$totalUnits = (int) $pdo->query('SELECT COALESCE(SUM(quantity), 0) FROM Inventory')->fetchColumn();

$recentMovements = $pdo->query(
    'SELECT p.product_name, w.warehouse_name, sm.movement_type, sm.quantity, sm.moved_at
     FROM Stock_Movement AS sm
     JOIN Product AS p ON p.product_id = sm.product_id
     JOIN Warehouse AS w ON w.warehouse_id = sm.warehouse_id
     ORDER BY sm.moved_at DESC, sm.movement_id DESC
     LIMIT 6'
)->fetchAll();

$lowStock = $pdo->query(
    'SELECT p.product_name, w.warehouse_name, i.quantity, p.reorder_level
     FROM v_low_stock AS ls
     JOIN Product AS p ON p.product_id = ls.product_id
     JOIN Warehouse AS w ON w.warehouse_id = ls.warehouse_id
     JOIN Inventory AS i ON i.product_id = ls.product_id AND i.warehouse_id = ls.warehouse_id
     ORDER BY (i.quantity - p.reorder_level) ASC
     LIMIT 6'
)->fetchAll();

$page_title = 'Dashboard | Inventory Manager';
require __DIR__ . '/includes/header.php';

page_header('Dashboard');
?>
<section class="grid-3">
    <article class="card">
        <p class="kpi__label"><?= icon('products', 16) ?>Total Products</p>
        <p class="kpi__value"><?= e((string) $totalProducts) ?></p>
        <p class="kpi__hint">Across <?= e((string) $warehouseCount) ?> warehouses</p>
    </article>
    <article class="card">
        <p class="kpi__label"><?= icon('inventory', 16) ?>Total Stock Value</p>
        <p class="kpi__value">₹<?= e(number_format($totalStockValue, 2)) ?></p>
        <p class="kpi__hint"><?= e(number_format($totalUnits)) ?> units on hand</p>
    </article>
    <article class="card">
        <p class="kpi__label"><?= icon('alert', 16) ?>Low-Stock Records</p>
        <p class="kpi__value"><?= e((string) $lowStockCount) ?></p>
        <p class="kpi__hint">At or below reorder level</p>
    </article>
</section>

<div class="grid-2">
    <section class="card">
        <div class="card__head">
            <h2 class="card__title"><?= icon('movements', 16) ?>Recent Movements</h2>
            <a class="button button--ghost button--sm" href="movements.php">View all</a>
        </div>
        <?php if ($recentMovements === []): ?>
            <?= empty_state('No stock movements recorded yet.', 'movements') ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Type</th>
                            <th class="num">Qty</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMovements as $item): ?>
                            <tr>
                                <td>
                                    <span class="cell-title" title="<?= e($item['product_name']) ?>"><?= e($item['product_name']) ?></span>
                                    <span class="cell-sub"><?= e($item['warehouse_name']) ?></span>
                                </td>
                                <td><?= movement_badge($item['movement_type']) ?></td>
                                <td class="num"><?= e((string) $item['quantity']) ?></td>
                                <td><?= e($item['moved_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card__head">
            <h2 class="card__title"><?= icon('alert', 16) ?>Low Stock</h2>
            <a class="button button--ghost button--sm" href="reports.php">Reports</a>
        </div>
        <?php if ($lowStock === []): ?>
            <?= empty_state('Every item is above its reorder level.', 'check') ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th class="num">Qty</th>
                            <th class="num">Reorder</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStock as $item): ?>
                            <tr>
                                <td><span class="cell-title" title="<?= e($item['product_name']) ?>"><?= e($item['product_name']) ?></span></td>
                                <td><?= e($item['warehouse_name']) ?></td>
                                <td class="num"><span class="badge badge--low"><?= e((string) $item['quantity']) ?></span></td>
                                <td class="num"><?= e((string) $item['reorder_level']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
