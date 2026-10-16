<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';

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

$listStatement = $pdo->prepare('SELECT warehouse_id, warehouse_name, location FROM Warehouse ORDER BY warehouse_name');
$listStatement->execute();
$warehouses = $listStatement->fetchAll();
$flash = get_flash();
$page_title = 'Warehouses | Inventory Manager';
require __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">Warehouses</h1>
<p class="page-subtitle">Manage warehouse locations.</p>
<?php if ($flash !== null): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>
<div class="content-grid">
    <section class="card">
        <h2><?= $warehouse['warehouse_id'] === '' ? 'Add Warehouse' : 'Edit Warehouse' ?></h2>
        <form method="post" data-validate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $warehouse['warehouse_id'] === '' ? 'create' : 'update' ?>">
            <input type="hidden" name="warehouse_id" value="<?= e((string) $warehouse['warehouse_id']) ?>">
            <div class="form-group">
                <label for="warehouse_name">Warehouse Name</label>
                <input id="warehouse_name" name="warehouse_name" maxlength="150" value="<?= e($warehouse['warehouse_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="location">Location</label>
                <input id="location" name="location" maxlength="200" value="<?= e((string) $warehouse['location']) ?>">
            </div>
            <button class="button" type="submit"><?= $warehouse['warehouse_id'] === '' ? 'Add Warehouse' : 'Save Changes' ?></button>
        </form>
    </section>
    <section class="card table-card">
        <table class="data-table">
            <thead><tr><th>Warehouse</th><th>Location</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($warehouses as $item): ?>
                    <tr>
                        <td><?= e($item['warehouse_name']) ?></td>
                        <td><?= e((string) $item['location']) ?></td>
                        <td><div class="action-group">
                            <a class="button button-small button-secondary" href="warehouses.php?edit=<?= e((string) $item['warehouse_id']) ?>">Edit</a>
                            <?php if (current_user()['role'] === 'admin'): ?>
                                <form class="inline-form" method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="warehouse_id" value="<?= e((string) $item['warehouse_id']) ?>">
                                    <button class="button button-small button-danger" type="submit">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
