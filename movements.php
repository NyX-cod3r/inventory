<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/ui.php';

require_login();

$errors = [];
$movement = ['product_id' => '', 'warehouse_id' => '', 'movement_type' => 'IN', 'quantity' => '', 'to_warehouse_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $warehouseId = filter_input(INPUT_POST, 'warehouse_id', FILTER_VALIDATE_INT);
    $movementType = (string) ($_POST['movement_type'] ?? '');
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $toWarehouseId = filter_input(INPUT_POST, 'to_warehouse_id', FILTER_VALIDATE_INT);
    $movement = [
        'product_id' => $productId ?: '',
        'warehouse_id' => $warehouseId ?: '',
        'movement_type' => $movementType,
        'quantity' => (string) ($_POST['quantity'] ?? ''),
        'to_warehouse_id' => $toWarehouseId ?: '',
    ];

    if ($productId === false || $productId === null || $productId <= 0) {
        $errors[] = 'Select a valid product.';
    }
    if ($warehouseId === false || $warehouseId === null || $warehouseId <= 0) {
        $errors[] = 'Select a valid source warehouse.';
    }
    if (!in_array($movementType, ['IN', 'OUT', 'TRANSFER'], true)) {
        $errors[] = 'Select a valid movement type.';
    }
    if ($quantity === false || $quantity === null || $quantity <= 0) {
        $errors[] = 'Quantity must be a whole number greater than zero.';
    }
    if ($movementType === 'TRANSFER' && ($toWarehouseId === false || $toWarehouseId === null || $toWarehouseId <= 0 || $toWarehouseId === $warehouseId)) {
        $errors[] = 'Select a different destination warehouse for a transfer.';
    }

    if ($errors === []) {
        try {
            $pdo->beginTransaction();

            $productStatement = $pdo->prepare('SELECT product_id FROM Product WHERE product_id = ? FOR UPDATE');
            $productStatement->execute([$productId]);
            if ($productStatement->fetch() === false) {
                throw new RuntimeException('The selected product no longer exists.');
            }

            $warehouseStatement = $pdo->prepare('SELECT warehouse_id FROM Warehouse WHERE warehouse_id = ?');
            $warehouseStatement->execute([$warehouseId]);
            if ($warehouseStatement->fetch() === false) {
                throw new RuntimeException('The selected source warehouse no longer exists.');
            }

            if ($movementType === 'TRANSFER') {
                $destinationStatement = $pdo->prepare('SELECT warehouse_id FROM Warehouse WHERE warehouse_id = ?');
                $destinationStatement->execute([$toWarehouseId]);
                if ($destinationStatement->fetch() === false) {
                    throw new RuntimeException('The selected destination warehouse no longer exists.');
                }
            }

            if ($movementType === 'OUT' || $movementType === 'TRANSFER') {
                $stockStatement = $pdo->prepare('SELECT quantity FROM Inventory WHERE product_id = ? AND warehouse_id = ? FOR UPDATE');
                $stockStatement->execute([$productId, $warehouseId]);
                $sourceInventory = $stockStatement->fetch();
                if ($sourceInventory === false || (int) $sourceInventory['quantity'] < $quantity) {
                    throw new RuntimeException('Insufficient stock in the source warehouse.');
                }
            }

            if ($movementType === 'IN') {
                $updateStatement = $pdo->prepare('INSERT INTO Inventory (warehouse_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?');
                $updateStatement->execute([$warehouseId, $productId, $quantity, $quantity]);
            }

            if ($movementType === 'OUT') {
                $updateStatement = $pdo->prepare('UPDATE Inventory SET quantity = quantity - ? WHERE warehouse_id = ? AND product_id = ?');
                $updateStatement->execute([$quantity, $warehouseId, $productId]);
            }

            if ($movementType === 'TRANSFER') {
                $sourceUpdateStatement = $pdo->prepare('UPDATE Inventory SET quantity = quantity - ? WHERE warehouse_id = ? AND product_id = ?');
                $sourceUpdateStatement->execute([$quantity, $warehouseId, $productId]);
                $destinationUpdateStatement = $pdo->prepare('INSERT INTO Inventory (warehouse_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?');
                $destinationUpdateStatement->execute([$toWarehouseId, $productId, $quantity, $quantity]);
            }

            $movementStatement = $pdo->prepare('INSERT INTO Stock_Movement (product_id, warehouse_id, movement_type, quantity) VALUES (?, ?, ?, ?)');
            $movementStatement->execute([$productId, $warehouseId, $movementType, $quantity]);
            $pdo->commit();

            set_flash('success', 'Stock movement recorded successfully.');
            header('Location: movements.php');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $exception instanceof RuntimeException ? $exception->getMessage() : 'The movement could not be recorded.';
        }
    }
}

$productStatement = $pdo->prepare('SELECT product_id, product_name FROM Product ORDER BY product_name');
$productStatement->execute();
$products = $productStatement->fetchAll();

$warehouseStatement = $pdo->prepare('SELECT warehouse_id, warehouse_name FROM Warehouse ORDER BY warehouse_name');
$warehouseStatement->execute();
$warehouses = $warehouseStatement->fetchAll();

$fromDate = trim((string) ($_GET['from_date'] ?? ''));
$toDate = trim((string) ($_GET['to_date'] ?? ''));
if ($fromDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) !== 1) {
    $fromDate = '';
}
if ($toDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate) !== 1) {
    $toDate = '';
}

$historyStatement = $pdo->prepare('SELECT sm.movement_id, p.product_name, w.warehouse_name, sm.movement_type, sm.quantity, sm.moved_at FROM Stock_Movement AS sm JOIN Product AS p ON p.product_id = sm.product_id JOIN Warehouse AS w ON w.warehouse_id = sm.warehouse_id WHERE (? = \'\' OR sm.moved_at >= ?) AND (? = \'\' OR sm.moved_at < DATE_ADD(?, INTERVAL 1 DAY)) ORDER BY sm.moved_at DESC, sm.movement_id DESC');
$historyStatement->execute([$fromDate, $fromDate, $toDate, $toDate]);
$history = $historyStatement->fetchAll();

$hasFilter = $fromDate !== '' || $toDate !== '';
$exportUrl = 'reports.php?' . http_build_query(['export' => 'movement_history', 'from_date' => $fromDate, 'to_date' => $toDate]);

$flash = get_flash();
$page_title = 'Stock Movements | Inventory Manager';
require __DIR__ . '/includes/header.php';

page_header(
    'Stock Movements',
    '<a class="button button--secondary" href="' . e($exportUrl) . '">' . icon('download', 16) . 'Export CSV</a>'
);
render_alerts($flash, $errors);
?>
<section class="card">
    <div class="card__head">
        <h2 class="card__title"><?= icon('plus', 16) ?>Record Movement</h2>
        <span class="movement-type movement-type--in"><span class="movement-type__dot" aria-hidden="true"></span>IN</span>
        <span class="movement-type movement-type--out"><span class="movement-type__dot" aria-hidden="true"></span>OUT</span>
        <span class="movement-type movement-type--transfer"><span class="movement-type__dot" aria-hidden="true"></span>TRANSFER</span>
    </div>
    <form method="post" data-validate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-grid">
            <div class="form-group">
                <label for="product_id">Product</label>
                <select id="product_id" name="product_id" required>
                    <option value="">Select product</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= e((string) $product['product_id']) ?>" <?= (string) $movement['product_id'] === (string) $product['product_id'] ? 'selected' : '' ?>><?= e($product['product_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="warehouse_id">Source Warehouse</label>
                <select id="warehouse_id" name="warehouse_id" required>
                    <option value="">Select warehouse</option>
                    <?php foreach ($warehouses as $warehouse): ?>
                        <option value="<?= e((string) $warehouse['warehouse_id']) ?>" <?= (string) $movement['warehouse_id'] === (string) $warehouse['warehouse_id'] ? 'selected' : '' ?>><?= e($warehouse['warehouse_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="movement_type">Movement Type</label>
                <select id="movement_type" name="movement_type" required>
                    <option value="IN" <?= $movement['movement_type'] === 'IN' ? 'selected' : '' ?>>IN — stock in</option>
                    <option value="OUT" <?= $movement['movement_type'] === 'OUT' ? 'selected' : '' ?>>OUT — stock out</option>
                    <option value="TRANSFER" <?= $movement['movement_type'] === 'TRANSFER' ? 'selected' : '' ?>>TRANSFER — move between warehouses</option>
                </select>
            </div>
            <div id="transfer-field" class="form-group transfer-field" hidden>
                <label for="to_warehouse_id">Destination Warehouse</label>
                <select id="to_warehouse_id" name="to_warehouse_id">
                    <option value="">Select destination</option>
                    <?php foreach ($warehouses as $warehouse): ?>
                        <option value="<?= e((string) $warehouse['warehouse_id']) ?>" <?= (string) $movement['to_warehouse_id'] === (string) $warehouse['warehouse_id'] ? 'selected' : '' ?>><?= e($warehouse['warehouse_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input id="quantity" name="quantity" type="number" min="1" step="1" value="<?= e($movement['quantity']) ?>" required>
            </div>
        </div>
        <button class="button" type="submit" style="margin-top: var(--sp-4)"><?= icon('check', 16) ?>Record Movement</button>
    </form>
</section>

<section class="card">
    <div class="card__head">
        <h2 class="card__title"><?= icon('clock', 16) ?>Movement History</h2>
        <span class="badge"><?= e((string) count($history)) ?> records</span>
    </div>
    <form class="toolbar" method="get" style="margin-bottom: var(--sp-4)">
        <div class="form-group">
            <label for="from_date">From Date</label>
            <input id="from_date" name="from_date" type="date" value="<?= e($fromDate) ?>">
        </div>
        <div class="form-group">
            <label for="to_date">To Date</label>
            <input id="to_date" name="to_date" type="date" value="<?= e($toDate) ?>">
        </div>
        <button class="button" type="submit"><?= icon('filter', 16) ?>Apply Filters</button>
        <?php if ($hasFilter): ?>
            <a class="button button--ghost" href="movements.php"><?= icon('close', 16) ?>Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($history === []): ?>
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
                    <?php foreach ($history as $item): ?>
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
            <span><?= e((string) count($history)) ?> movement<?= count($history) === 1 ? '' : 's' ?></span>
            <?php if ($hasFilter): ?><span>Filtered by date range</span><?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
