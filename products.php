<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/ui.php';

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

$isEdit = $product['product_id'] !== '';
$drawerOpen = $isEdit || isset($_GET['new']);

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

$isAdmin = current_user()['role'] === 'admin';
page_header(
    'Products',
    'Manage products, supplier assignments, prices, and reorder levels.',
    '<a class="button" href="products.php?new=1" data-drawer-open>' . icon('plus', 16) . 'Add Product</a>'
);
render_alerts($flash, $errors);
?>
<section class="card">
    <div class="card__head">
        <h2 class="card__title"><?= icon('products', 16) ?>All Products</h2>
        <span class="badge"><?= e((string) count($products)) ?> total</span>
    </div>
    <?php if ($products === []): ?>
        <?= empty_state('No products yet. Add your first product to get started.', 'products') ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th class="num">Price</th>
                        <th class="num">Reorder</th>
                        <?php if ($isAdmin): ?><th class="cell-actions">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $item): ?>
                        <tr>
                            <td>
                                <span class="cell-title" title="<?= e($item['product_name']) ?>"><?= e($item['product_name']) ?></span>
                            </td>
                            <td><?= e($item['category_name']) ?></td>
                            <td><?= e($item['supplier_name']) ?></td>
                            <td class="num">₹<?= e(number_format((float) $item['unit_price'], 2)) ?></td>
                            <td class="num"><?= e((string) $item['reorder_level']) ?></td>
                            <?php if ($isAdmin): ?>
                                <td class="cell-actions">
                                    <div class="button-group">
                                        <a class="button button--secondary button--sm" href="products.php?edit=<?= e((string) $item['product_id']) ?>">
                                            <?= icon('edit', 15) ?>Edit
                                        </a>
                                        <form class="inline-form" method="post" data-confirm="Delete this product? This cannot be undone.">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?= e((string) $item['product_id']) ?>">
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
    <input type="hidden" name="product_id" value="<?= e((string) $product['product_id']) ?>">
    <?= drawer_open('products.php', $isEdit ? 'Edit Product' : 'Add Product', 'products', $drawerOpen) ?>
    <div class="form-group">
        <label for="product_name">Product Name</label>
        <input id="product_name" name="product_name" maxlength="150" value="<?= e($product['product_name']) ?>" required>
    </div>
    <div class="form-grid">
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
            <label for="unit_price">Unit Price</label>
            <input id="unit_price" name="unit_price" type="number" min="0" max="99999999.99" step="0.01" value="<?= e((string) $product['unit_price']) ?>" required>
        </div>
        <div class="form-group">
            <label for="reorder_level">Reorder Level</label>
            <input id="reorder_level" name="reorder_level" type="number" min="0" step="1" value="<?= e((string) $product['reorder_level']) ?>" required>
        </div>
    </div>
    <?= drawer_close('products.php', $isEdit ? 'Save Changes' : 'Add Product') ?>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
