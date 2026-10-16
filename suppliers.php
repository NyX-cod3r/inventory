<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/ui.php';

require_login();

$errors = [];
$supplier = ['supplier_id' => '', 'supplier_name' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = $_POST['action'] ?? '';
    $supplierId = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT);
    $supplierName = trim((string) ($_POST['supplier_name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));

    if ($action === 'delete') {
        require_admin();
        if ($supplierId === false || $supplierId === null || $supplierId <= 0) {
            $errors[] = 'Invalid supplier.';
        } else {
            try {
                $statement = $pdo->prepare('DELETE FROM Supplier WHERE supplier_id = ?');
                $statement->execute([$supplierId]);
                set_flash('success', 'Supplier deleted.');
                header('Location: suppliers.php');
                exit;
            } catch (PDOException $exception) {
                $errors[] = 'This supplier cannot be deleted because products use it.';
            }
        }
    } elseif ($action === 'create' || $action === 'update') {
        if ($supplierName === '' || strlen($supplierName) > 150) {
            $errors[] = 'Supplier name is required and must be 150 characters or fewer.';
        }
        if (strlen($phone) > 25) {
            $errors[] = 'Phone must be 25 characters or fewer.';
        }
        if ($action === 'update' && ($supplierId === false || $supplierId === null || $supplierId <= 0)) {
            $errors[] = 'Invalid supplier.';
        }

        if ($errors === []) {
            if ($action === 'create') {
                $statement = $pdo->prepare('INSERT INTO Supplier (supplier_name, phone) VALUES (?, ?)');
                $statement->execute([$supplierName, $phone === '' ? null : $phone]);
                set_flash('success', 'Supplier added.');
            } else {
                $statement = $pdo->prepare('UPDATE Supplier SET supplier_name = ?, phone = ? WHERE supplier_id = ?');
                $statement->execute([$supplierName, $phone === '' ? null : $phone, $supplierId]);
                set_flash('success', 'Supplier updated.');
            }
            header('Location: suppliers.php');
            exit;
        }

        $supplier = ['supplier_id' => $supplierId ?: '', 'supplier_name' => $supplierName, 'phone' => $phone];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId !== false && $editId !== null && $editId > 0) {
        $statement = $pdo->prepare('SELECT supplier_id, supplier_name, phone FROM Supplier WHERE supplier_id = ?');
        $statement->execute([$editId]);
        $result = $statement->fetch();
        if ($result !== false) {
            $supplier = $result;
        }
    }
}

$isEdit = $supplier['supplier_id'] !== '';
$drawerOpen = $isEdit || isset($_GET['new']);

$listStatement = $pdo->prepare('SELECT supplier_id, supplier_name, phone FROM Supplier ORDER BY supplier_name');
$listStatement->execute();
$suppliers = $listStatement->fetchAll();
$flash = get_flash();
$page_title = 'Suppliers | Inventory Manager';
require __DIR__ . '/includes/header.php';

$isAdmin = current_user()['role'] === 'admin';
page_header(
    'Suppliers',
    'Manage supplier contact details.',
    '<a class="button" href="suppliers.php?new=1" data-drawer-open>' . icon('plus', 16) . 'Add Supplier</a>'
);
render_alerts($flash, $errors);
?>
<section class="card">
    <div class="card__head">
        <h2 class="card__title"><?= icon('suppliers', 16) ?>All Suppliers</h2>
        <span class="badge"><?= e((string) count($suppliers)) ?> total</span>
    </div>
    <?php if ($suppliers === []): ?>
        <?= empty_state('No suppliers yet. Add your first supplier to get started.', 'suppliers') ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Phone</th>
                        <?php if ($isAdmin): ?><th class="cell-actions">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $item): ?>
                        <tr>
                            <td>
                                <span class="cell-title" title="<?= e($item['supplier_name']) ?>"><?= e($item['supplier_name']) ?></span>
                            </td>
                            <td><?= $item['phone'] !== null && $item['phone'] !== '' ? e($item['phone']) : '<span class="cell-sub">—</span>' ?></td>
                            <?php if ($isAdmin): ?>
                                <td class="cell-actions">
                                    <div class="button-group">
                                        <a class="button button--secondary button--sm" href="suppliers.php?edit=<?= e((string) $item['supplier_id']) ?>">
                                            <?= icon('edit', 15) ?>Edit
                                        </a>
                                        <form class="inline-form" method="post" data-confirm="Delete this supplier? This cannot be undone.">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="supplier_id" value="<?= e((string) $item['supplier_id']) ?>">
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
    <input type="hidden" name="supplier_id" value="<?= e((string) $supplier['supplier_id']) ?>">
    <?= drawer_open('suppliers.php', $isEdit ? 'Edit Supplier' : 'Add Supplier', 'suppliers', $drawerOpen) ?>
    <div class="form-group">
        <label for="supplier_name">Supplier Name</label>
        <input id="supplier_name" name="supplier_name" maxlength="150" value="<?= e($supplier['supplier_name']) ?>" required>
    </div>
    <div class="form-group">
        <label for="phone">Phone</label>
        <input id="phone" name="phone" maxlength="25" value="<?= e((string) $supplier['phone']) ?>">
    </div>
    <?= drawer_close('suppliers.php', $isEdit ? 'Save Changes' : 'Add Supplier') ?>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
