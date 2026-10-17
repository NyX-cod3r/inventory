<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/ui.php';

require_login();

function low_stock_report(PDO $pdo): array
{
    $statement = $pdo->prepare('SELECT product_name, category_name, warehouse_name, quantity, reorder_level FROM v_low_stock ORDER BY product_name, warehouse_name');
    $statement->execute();

    return $statement->fetchAll();
}

function warehouse_value_report(PDO $pdo): array
{
    $statement = $pdo->prepare('SELECT w.warehouse_name, COALESCE(SUM(i.quantity), 0) AS total_units, COALESCE(SUM(i.quantity * p.unit_price), 0) AS total_value FROM Warehouse AS w LEFT JOIN Inventory AS i ON i.warehouse_id = w.warehouse_id LEFT JOIN Product AS p ON p.product_id = i.product_id GROUP BY w.warehouse_id, w.warehouse_name ORDER BY w.warehouse_name');
    $statement->execute();

    return $statement->fetchAll();
}

function movement_history_report(PDO $pdo, string $fromDate, string $toDate): array
{
    $statement = $pdo->prepare('SELECT sm.movement_id, p.product_name, w.warehouse_name, sm.movement_type, sm.quantity, sm.moved_at FROM Stock_Movement AS sm JOIN Product AS p ON p.product_id = sm.product_id JOIN Warehouse AS w ON w.warehouse_id = sm.warehouse_id WHERE (? = \'\' OR sm.moved_at >= ?) AND (? = \'\' OR sm.moved_at < DATE_ADD(?, INTERVAL 1 DAY)) ORDER BY sm.moved_at DESC, sm.movement_id DESC');
    $statement->execute([$fromDate, $fromDate, $toDate, $toDate]);

    return $statement->fetchAll();
}

function download_csv(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

$fromDate = trim((string) ($_GET['from_date'] ?? ''));
$toDate = trim((string) ($_GET['to_date'] ?? ''));
if ($fromDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) !== 1) {
    $fromDate = '';
}
if ($toDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate) !== 1) {
    $toDate = '';
}

$export = $_GET['export'] ?? '';
if ($export === 'low_stock') {
    $rows = [];
    foreach (low_stock_report($pdo) as $item) {
        $rows[] = [$item['product_name'], $item['category_name'], $item['warehouse_name'], $item['quantity'], $item['reorder_level']];
    }
    download_csv('low-stock-report.csv', ['Product', 'Category', 'Warehouse', 'Quantity', 'Reorder Level'], $rows);
}
if ($export === 'warehouse_value') {
    $rows = [];
    foreach (warehouse_value_report($pdo) as $item) {
        $rows[] = [$item['warehouse_name'], $item['total_units'], number_format((float) $item['total_value'], 2, '.', '')];
    }
    download_csv('warehouse-value-report.csv', ['Warehouse', 'Total Units', 'Inventory Value'], $rows);
}
if ($export === 'movement_history') {
    $rows = [];
    foreach (movement_history_report($pdo, $fromDate, $toDate) as $item) {
        $rows[] = [$item['moved_at'], $item['product_name'], $item['warehouse_name'], $item['movement_type'], $item['quantity']];
    }
    download_csv('movement-history-report.csv', ['Moved At', 'Product', 'Warehouse', 'Type', 'Quantity'], $rows);
}

$lowStock = low_stock_report($pdo);
$warehouseValues = warehouse_value_report($pdo);
$movementHistory = movement_history_report($pdo, $fromDate, $toDate);
$movementExportUrl = 'reports.php?' . http_build_query(['export' => 'movement_history', 'from_date' => $fromDate, 'to_date' => $toDate]);
$hasFilter = $fromDate !== '' || $toDate !== '';

$totalValue = 0.0;
foreach ($warehouseValues as $item) {
    $totalValue += (float) $item['total_value'];
}

$page_title = 'Reports | Inventory Manager';
require __DIR__ . '/includes/header.php';

page_header('Reports');
?>
<section class="card">
    <div class="card__head">
        <h2 class="card__title"><?= icon('filter', 16) ?>Movement Date Range</h2>
        <?php if ($hasFilter): ?>
            <a class="button button--ghost button--sm" href="reports.php"><?= icon('close', 15) ?>Clear</a>
        <?php endif; ?>
    </div>
    <form class="toolbar" method="get">
        <input type="hidden" name="export" value="movement_history">
        <div class="form-group">
            <label for="from_date">From Date</label>
            <input id="from_date" name="from_date" type="date" value="<?= e($fromDate) ?>">
        </div>
        <div class="form-group">
            <label for="to_date">To Date</label>
            <input id="to_date" name="to_date" type="date" value="<?= e($toDate) ?>">
        </div>
        <button class="button" type="submit"><?= icon('filter', 16) ?>Apply to History</button>
    </form>
</section>

<div class="stack">
    <section class="card">
        <div class="card__head">
            <h2 class="card__title"><?= icon('alert', 16) ?>Low-Stock Alerts</h2>
            <a class="button button--secondary button--sm" href="reports.php?export=low_stock"><?= icon('download', 15) ?>Export CSV</a>
        </div>
        <?php if ($lowStock === []): ?>
            <?= empty_state('Every item is above its reorder level.', 'check') ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Warehouse</th>
                            <th class="num">Quantity</th>
                            <th class="num">Reorder Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStock as $item): ?>
                            <tr>
                                <td><span class="cell-title" title="<?= e($item['product_name']) ?>"><?= e($item['product_name']) ?></span></td>
                                <td><?= e($item['category_name']) ?></td>
                                <td><?= e($item['warehouse_name']) ?></td>
                                <td class="num"><span class="badge badge--low"><?= e((string) $item['quantity']) ?></span></td>
                                <td class="num"><?= e((string) $item['reorder_level']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-foot"><span><?= e((string) count($lowStock)) ?> item<?= count($lowStock) === 1 ? '' : 's' ?> below reorder level</span></div>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card__head">
            <h2 class="card__title"><?= icon('warehouses', 16) ?>Inventory Value by Warehouse</h2>
            <a class="button button--secondary button--sm" href="reports.php?export=warehouse_value"><?= icon('download', 15) ?>Export CSV</a>
        </div>
        <?php if ($warehouseValues === []): ?>
            <?= empty_state('No warehouses have been recorded yet.', 'warehouses') ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Warehouse</th>
                            <th class="num">Total Units</th>
                            <th class="num">Inventory Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($warehouseValues as $item): ?>
                            <tr>
                                <td><span class="cell-title"><?= e($item['warehouse_name']) ?></span></td>
                                <td class="num"><?= e(number_format((int) $item['total_units'])) ?></td>
                                <td class="num">₹<?= e(number_format((float) $item['total_value'], 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-foot"><span>Total across all warehouses</span><span>₹<?= e(number_format($totalValue, 2)) ?></span></div>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card__head">
            <h2 class="card__title"><?= icon('movements', 16) ?>Movement History</h2>
            <a class="button button--secondary button--sm" href="<?= e($movementExportUrl) ?>"><?= icon('download', 15) ?>Export CSV</a>
        </div>
        <?php if ($movementHistory === []): ?>
            <?= empty_state('No movements match this date range.', 'movements') ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th>Type</th>
                            <th class="num">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movementHistory as $item): ?>
                            <tr>
                                <td><?= e($item['moved_at']) ?></td>
                                <td><span class="cell-title" title="<?= e($item['product_name']) ?>"><?= e($item['product_name']) ?></span></td>
                                <td><?= e($item['warehouse_name']) ?></td>
                                <td><?= movement_badge($item['movement_type']) ?></td>
                                <td class="num"><?= e((string) $item['quantity']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-foot">
                <span><?= e((string) count($movementHistory)) ?> movement<?= count($movementHistory) === 1 ? '' : 's' ?></span>
                <?php if ($hasFilter): ?><span>Filtered by date range</span><?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
