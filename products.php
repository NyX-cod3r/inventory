<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';

require_login();

$errors = [];
$product = [
    'product_id' => '',
    'category_id' => '',
    'supplier_id' => '',
    'product_name' => '',
    'unit_price' => '',
    'reorder_level' => '10',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = $_POST['action'] ?? '';
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $supplierId = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT);
    $productName = trim((string) ($_POST['product_name'] ?? ''));
    $unitPrice = filter_input(INPUT_POST, 'unit_price', FILTER_VALIDATE_FLOAT);
    $reorderLevel = filter_input(INPUT_POST, 'reorder_level', FILTER_VALIDATE_INT);

    if ($action === 'delete') {
        require_admin();
        if ($productId === false || $productId === null || $productId <= 0) {
            $errors[] = 'Invalid product.';
        } else {
            try {
                $statement = $pdo->prepare('DELETE FROM Product WHERE product_id = ?');
                $statement->execute([$productId]);
                set_flash('success', 'Product deleted.');
                header('Location: products.php');
                exit;
            } catch (PDOException $exception) {
                $errors[] = 'This product cannot be deleted because inventory or movements use it.';
            }
        }
    } elseif ($action === 'create' || $action === 'update') {
        if ($categoryId === false || $categoryId === null || $categoryId <= 0) {
            $errors[] = 'Select a valid category.';
        }
        if ($supplierId === false || $supplierId === null || $supplierId <= 0) {
            $errors[] = 'Select a valid supplier.';
        }
        if ($productName === '' || strlen($productName) > 150) {
            $errors[] = 'Product name is required and must be 150 characters or fewer.';
        }
        if ($unitPrice === false || $unitPrice === null || $unitPrice < 0 || $unitPrice > 99999999.99) {
            $errors[] = 'Enter a valid unit price.';
        }
        if ($reorderLevel === false || $reorderLevel === null || $reorderLevel < 0) {
            $errors[] = 'Reorder level must be zero or greater.';
        }
        if ($action === 'update' && ($productId === false || $productId === null || $productId <= 0)) {
            $errors[] = 'Invalid product.';
        }

        if ($errors === []) {
            try {
                if ($action === 'create') {
                    $statement = $pdo->prepare('INSERT INTO Product (category_id, supplier_id, product_name, unit_price, reorder_level) VALUES (?, ?, ?, ?, ?)');
                    $statement->execute([$categoryId, $supplierId, $productName, $unitPrice, $reorderLevel]);
                    set_flash('success', 'Product added.');
                } else {
                    $statement = $pdo->prepare('UPDATE Product SET category_id = ?, supplier_id = ?, product_name = ?, unit_price = ?, reorder_level = ? WHERE product_id = ?');
                    $statement->execute([$categoryId, $supplierId, $productName, $unitPrice, $reorderLevel, $productId]);
                    set_flash('success', 'Product updated.');
                }
                header('Location: products.php');
                exit;
            } catch (PDOException $exception) {
                $errors[] = 'The product could not be saved. Check its category and supplier.';
            }
        }

        $product = [
            'product_id' => $productId ?: '',
            'category_id' => $categoryId ?: '',
            'supplier_id' => $supplierId ?: '',
            'product_name' => $productName,
            'unit_price' => (string) ($_POST['unit_price'] ?? ''),
            'reorder_level' => (string) ($_POST['reorder_level'] ?? ''),
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId !== false && $editId !== null && $editId > 0) {
        $statement = $pdo->prepare('SELECT product_id, category_id, supplier_id, product_name, unit_price, reorder_level FROM Product WHERE product_id = ?');
        $statement->execute([$editId]);
        $result = $statement->fetch();
        if ($result !== false) {
            $product = $result;
        }
    }
}

$categoryStatement = $pdo->prepare('SELECT category_id, category_name FROM Category ORDER BY category_name');
$categoryStatement->execute();
$categories = $categoryStatement->fetchAll();

$supplierStatement = $pdo->prepare('SELECT supplier_id, supplier_name FROM Supplier ORDER BY supplier_name');
$supplierStatement->execute();
$suppliers = $supplierStatement->fetchAll();

$listStatement = $pdo->prepare('SELECT p.product_id, p.product_name, p.unit_price, p.reorder_level, c.category_name, s.supplier_name FROM Product AS p JOIN Category AS c ON c.category_id = p.category_id JOIN Supplier AS s ON s.supplier_id = p.supplier_id ORDER BY p.product_name');
$listStatement->execute();
$products = $listStatement->fetchAll();

$flash = get_flash();
$page_title = 'Products | Inventory Manager';
require __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">Products</h1>
<p class="page-subtitle">Manage products, supplier assignments, prices, and reorder levels.</p>
<?php if ($flash !== null): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>
<div class="content-grid">
    <section class="card">
        <h2><?= $product['product_id'] === '' ? 'Add Product' : 'Edit Product' ?></h2>
        <form method="post" data-validate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $product['product_id'] === '' ? 'create' : 'update' ?>">
            <input type="hidden" name="product_id" value="<?= e((string) $product['product_id']) ?>">
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $item): ?>
                        <option value="<?= e((string) $item['category_id']) ?>" <?= (string) $product['category_id'] === (string) $item['category_id'] ? 'selected' : '' ?>><?= e($item['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id" required>
                    <option value="">Select supplier</option>
                    <?php foreach ($suppliers as $item): ?>
                        <option value="<?= e((string) $item['supplier_id']) ?>" <?= (string) $product['supplier_id'] === (string) $item['supplier_id'] ? 'selected' : '' ?>><?= e($item['supplier_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="product_name">Product Name</label>
                <input id="product_name" name="product_name" maxlength="150" value="<?= e($product['product_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="unit_price">Unit Price</label>
                <input id="unit_price" name="unit_price" type="number" min="0" max="99999999.99" step="0.01" value="<?= e((string) $product['unit_price']) ?>" required>
            </div>
            <div class="form-group">
                <label for="reorder_level">Reorder Level</label>
                <input id="reorder_level" name="reorder_level" type="number" min="0" step="1" value="<?= e((string) $product['reorder_level']) ?>" required>
            </div>
            <button class="button" type="submit"><?= $product['product_id'] === '' ? 'Add Product' : 'Save Changes' ?></button>
        </form>
    </section>
    <section class="card table-card">
        <table class="data-table">
            <thead><tr><th>Product</th><th>Category</th><th>Supplier</th><th>Price</th><th>Reorder</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($products as $item): ?>
                    <tr>
                        <td><?= e($item['product_name']) ?></td>
                        <td><?= e($item['category_name']) ?></td>
                        <td><?= e($item['supplier_name']) ?></td>
                        <td>₹<?= e(number_format((float) $item['unit_price'], 2)) ?></td>
                        <td><?= e((string) $item['reorder_level']) ?></td>
                        <td><div class="action-group">
                            <a class="button button-small button-secondary" href="products.php?edit=<?= e((string) $item['product_id']) ?>">Edit</a>
                            <?php if (current_user()['role'] === 'admin'): ?>
                                <form class="inline-form" method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?= e((string) $item['product_id']) ?>">
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
