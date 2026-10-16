<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/ui.php';

require_login();

$errors = [];
$category = ['category_id' => '', 'category_name' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = $_POST['action'] ?? '';
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $categoryName = trim((string) ($_POST['category_name'] ?? ''));

    if ($action === 'delete') {
        require_admin();
        if ($categoryId === false || $categoryId === null || $categoryId <= 0) {
            $errors[] = 'Invalid category.';
        } else {
            try {
                $statement = $pdo->prepare('DELETE FROM Category WHERE category_id = ?');
                $statement->execute([$categoryId]);
                set_flash('success', 'Category deleted.');
                header('Location: categories.php');
                exit;
            } catch (PDOException $exception) {
                $errors[] = 'This category cannot be deleted because products use it.';
            }
        }
    } elseif ($action === 'create' || $action === 'update') {
        if ($categoryName === '' || strlen($categoryName) > 100) {
            $errors[] = 'Category name is required and must be 100 characters or fewer.';
        }

        if ($action === 'update' && ($categoryId === false || $categoryId === null || $categoryId <= 0)) {
            $errors[] = 'Invalid category.';
        }

        if ($errors === []) {
            try {
                if ($action === 'create') {
                    $statement = $pdo->prepare('INSERT INTO Category (category_name) VALUES (?)');
                    $statement->execute([$categoryName]);
                    set_flash('success', 'Category added.');
                } else {
                    $statement = $pdo->prepare('UPDATE Category SET category_name = ? WHERE category_id = ?');
                    $statement->execute([$categoryName, $categoryId]);
                    set_flash('success', 'Category updated.');
                }
                header('Location: categories.php');
                exit;
            } catch (PDOException $exception) {
                $errors[] = 'A category with this name already exists.';
            }
        }

        $category = ['category_id' => $categoryId ?: '', 'category_name' => $categoryName];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId !== false && $editId !== null && $editId > 0) {
        $statement = $pdo->prepare('SELECT category_id, category_name FROM Category WHERE category_id = ?');
        $statement->execute([$editId]);
        $result = $statement->fetch();
        if ($result !== false) {
            $category = $result;
        }
    }
}

$isEdit = $category['category_id'] !== '';
$drawerOpen = $isEdit || isset($_GET['new']);

$listStatement = $pdo->prepare('SELECT category_id, category_name FROM Category ORDER BY category_name');
$listStatement->execute();
$categories = $listStatement->fetchAll();
$flash = get_flash();
$page_title = 'Categories | Inventory Manager';
require __DIR__ . '/includes/header.php';

$isAdmin = current_user()['role'] === 'admin';
page_header(
    'Categories',
    'Create and maintain product categories.',
    '<a class="button" href="categories.php?new=1" data-drawer-open>' . icon('plus', 16) . 'Add Category</a>'
);
render_alerts($flash, $errors);
?>
<section class="card">
    <div class="card__head">
        <h2 class="card__title"><?= icon('categories', 16) ?>All Categories</h2>
        <span class="badge"><?= e((string) count($categories)) ?> total</span>
    </div>
    <?php if ($categories === []): ?>
        <?= empty_state('No categories yet. Add your first category to get started.', 'categories') ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <?php if ($isAdmin): ?><th class="cell-actions">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $item): ?>
                        <tr>
                            <td>
                                <span class="cell-title" title="<?= e($item['category_name']) ?>"><?= e($item['category_name']) ?></span>
                            </td>
                            <?php if ($isAdmin): ?>
                                <td class="cell-actions">
                                    <div class="button-group">
                                        <a class="button button--secondary button--sm" href="categories.php?edit=<?= e((string) $item['category_id']) ?>">
                                            <?= icon('edit', 15) ?>Edit
                                        </a>
                                        <form class="inline-form" method="post" data-confirm="Delete this category? This cannot be undone.">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="category_id" value="<?= e((string) $item['category_id']) ?>">
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
    <input type="hidden" name="category_id" value="<?= e((string) $category['category_id']) ?>">
    <?= drawer_open('categories.php', $isEdit ? 'Edit Category' : 'Add Category', 'categories', $drawerOpen) ?>
    <div class="form-group">
        <label for="category_name">Category Name</label>
        <input id="category_name" name="category_name" maxlength="100" value="<?= e($category['category_name']) ?>" required>
    </div>
    <?= drawer_close('categories.php', $isEdit ? 'Save Changes' : 'Add Category') ?>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
