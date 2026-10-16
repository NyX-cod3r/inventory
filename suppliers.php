<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';

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

$listStatement = $pdo->prepare('SELECT supplier_id, supplier_name, phone FROM Supplier ORDER BY supplier_name');
$listStatement->execute();
$suppliers = $listStatement->fetchAll();
$flash = get_flash();
$page_title = 'Suppliers | Inventory Manager';
require __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">Suppliers</h1>
<p class="page-subtitle">Manage supplier contact details.</p>
<?php if ($flash !== null): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>
<div class="content-grid">
    <section class="card">
        <h2><?= $supplier['supplier_id'] === '' ? 'Add Supplier' : 'Edit Supplier' ?></h2>
        <form method="post" data-validate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $supplier['supplier_id'] === '' ? 'create' : 'update' ?>">
            <input type="hidden" name="supplier_id" value="<?= e((string) $supplier['supplier_id']) ?>">
            <div class="form-group">
                <label for="supplier_name">Supplier Name</label>
                <input id="supplier_name" name="supplier_name" maxlength="150" value="<?= e($supplier['supplier_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input id="phone" name="phone" maxlength="25" value="<?= e((string) $supplier['phone']) ?>">
            </div>
            <button class="button" type="submit"><?= $supplier['supplier_id'] === '' ? 'Add Supplier' : 'Save Changes' ?></button>
        </form>
    </section>
    <section class="card table-card">
        <table class="data-table">
            <thead><tr><th>Supplier</th><th>Phone</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($suppliers as $item): ?>
                    <tr>
                        <td><?= e($item['supplier_name']) ?></td>
                        <td><?= e((string) $item['phone']) ?></td>
                        <td><div class="action-group">
                            <a class="button button-small button-secondary" href="suppliers.php?edit=<?= e((string) $item['supplier_id']) ?>">Edit</a>
                            <?php if (current_user()['role'] === 'admin'): ?>
                                <form class="inline-form" method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="supplier_id" value="<?= e((string) $item['supplier_id']) ?>">
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
