<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/ui.php';

require_login();

$errors = [];
$warehouse = ['warehouse_id' => '', 'warehouse_name' => '', 'location' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = $_POST['action'] ?? '';
    $warehouseId = filter_input(INPUT_POST, 'warehouse_id', FILTER_VALIDATE_INT);
    $warehouseName = trim((string) ($_POST['warehouse_name'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));

    if ($action === 'delete') {
        require_admin();
        if ($warehouseId === false || $warehouseId === null || $warehouseId <= 0) {
            $errors[] = 'Invalid warehouse.';
        } else {
            try {
                $statement = $pdo->prepare('DELETE FROM Warehouse WHERE warehouse_id = ?');
                $statement->execute([$warehouseId]);
                set_flash('success', 'Warehouse deleted.');
                header('Location: warehouses.php');
                exit;
            } catch (PDOException $exception) {
                $errors[] = 'This warehouse cannot be deleted because inventory or movements use it.';
            }
        }
    } elseif ($action === 'create' || $action === 'update') {
        if ($warehouseName === '' || strlen($warehouseName) > 150) {
            $errors[] = 'Warehouse name is required and must be 150 characters or fewer.';
        }
        if (strlen($location) > 200) {
            $errors[] = 'Location must be 200 characters or fewer.';
        }
        if ($action === 'update' && ($warehouseId === false || $warehouseId === null || $warehouseId <= 0)) {
            $errors[] = 'Invalid warehouse.';
        }

        if ($errors === []) {
            if ($action === 'create') {
                $statement = $pdo->prepare('INSERT INTO Warehouse (warehouse_name, location) VALUES (?, ?)');
                $statement->execute([$warehouseName, $location === '' ? null : $location]);
                set_flash('success', 'Warehouse added.');
            } else {
                $statement = $pdo->prepare('UPDATE Warehouse SET warehouse_name = ?, location = ? WHERE warehouse_id = ?');
                $statement->execute([$warehouseName, $location === '' ? null : $location, $warehouseId]);
                set_flash('success', 'Warehouse updated.');
            }
            header('Location: warehouses.php');
            exit;
        }

        $warehouse = ['warehouse_id' => $warehouseId ?: '', 'warehouse_name' => $warehouseName, 'location' => $location];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId !== false && $editId !== null && $editId > 0) {
        $statement = $pdo->prepare('SELECT warehouse_id, warehouse_name, location FROM Warehouse WHERE warehouse_id = ?');
        $statement->execute([$editId]);
        $result = $statement->fetch();
        if ($result !== false) {
            $warehouse = $result;
        }
    }
}

$isEdit = $warehouse['warehouse_id'] !== '';
$drawerOpen = $isEdit || isset($_GET['new']);

$listStatement = $pdo->prepare('SELECT warehouse_id, warehouse_name, location FROM Warehouse ORDER BY warehouse_name');
$listStatement->execute();
$warehouses = $listStatement->fetchAll();
$flash = get_flash();
$page_title = 'Warehouses | Inventory Manager';
require __DIR__ . '/includes/header.php';

$isAdmin = current_user()['role'] === 'admin';
page_header(
    'Warehouses',
    '<a class="button" href="warehouses.php?new=1" data-drawer-open>' . icon('plus', 16) . 'Add Warehouse</a>'
);
render_alerts($flash, $errors);
?>
<section class="card">
    <div class="card__head">
        <h2 class="card__title"><?= icon('warehouses', 16) ?>All Warehouses</h2>
        <span class="badge"><?= e((string) count($warehouses)) ?> total</span>
    </div>
    <?php if ($warehouses === []): ?>
        <?= empty_state('No warehouses yet. Add your first warehouse to get started.', 'warehouses') ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Warehouse</th>
                        <th>Location</th>
                        <?php if ($isAdmin): ?><th class="cell-actions">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($warehouses as $item): ?>
                        <tr>
                            <td>
                                <span class="cell-title" title="<?= e($item['warehouse_name']) ?>"><?= e($item['warehouse_name']) ?></span>
                            </td>
                            <td><?= $item['location'] !== null && $item['location'] !== '' ? e($item['location']) : '<span class="cell-sub">—</span>' ?></td>
                            <?php if ($isAdmin): ?>
                                <td class="cell-actions">
                                    <div class="button-group">
                                        <a class="button button--secondary button--sm" href="warehouses.php?edit=<?= e((string) $item['warehouse_id']) ?>">
                                            <?= icon('edit', 15) ?>Edit
                                        </a>
                                        <form class="inline-form" method="post" data-confirm="Delete this warehouse? This cannot be undone.">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="warehouse_id" value="<?= e((string) $item['warehouse_id']) ?>">
                                            <button class="button button--danger button--sm button--block" type="submit">
                                                <?= icon('trash', 15) ?>Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<form method="post" data-validate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
    <input type="hidden" name="warehouse_id" value="<?= e((string) $warehouse['warehouse_id']) ?>">
    <?= drawer_open('warehouses.php', $isEdit ? 'Edit Warehouse' : 'Add Warehouse', 'warehouses', $drawerOpen) ?>
    <div class="form-group">
        <label for="warehouse_name">Warehouse Name</label>
        <input id="warehouse_name" name="warehouse_name" maxlength="150" value="<?= e($warehouse['warehouse_name']) ?>" required>
    </div>
    <div class="form-group">
        <label for="location">Location</label>
        <input id="location" name="location" maxlength="200" value="<?= e((string) $warehouse['location']) ?>">
    </div>
    <?= drawer_close('warehouses.php', $isEdit ? 'Save Changes' : 'Add Warehouse') ?>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
