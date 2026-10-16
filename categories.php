<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';

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

$listStatement = $pdo->prepare('SELECT category_id, category_name FROM Category ORDER BY category_name');
$listStatement->execute();
$categories = $listStatement->fetchAll();
$flash = get_flash();
$page_title = 'Categories | Inventory Manager';
require __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">Categories</h1>
<p class="page-subtitle">Create and maintain product categories.</p>
<?php if ($flash !== null): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>
<div class="content-grid">
    <section class="card">
        <h2><?= $category['category_id'] === '' ? 'Add Category' : 'Edit Category' ?></h2>
        <form method="post" data-validate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $category['category_id'] === '' ? 'create' : 'update' ?>">
            <input type="hidden" name="category_id" value="<?= e((string) $category['category_id']) ?>">
            <div class="form-group">
                <label for="category_name">Category Name</label>
                <input id="category_name" name="category_name" maxlength="100" value="<?= e($category['category_name']) ?>" required>
            </div>
            <button class="button" type="submit"><?= $category['category_id'] === '' ? 'Add Category' : 'Save Changes' ?></button>
        </form>
    </section>
    <section class="card table-card">
        <table class="data-table">
            <thead><tr><th>Name</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($categories as $item): ?>
                    <tr>
                        <td><?= e($item['category_name']) ?></td>
                        <td><div class="action-group">
                            <a class="button button-small button-secondary" href="categories.php?edit=<?= e((string) $item['category_id']) ?>">Edit</a>
                            <?php if (current_user()['role'] === 'admin'): ?>
                                <form class="inline-form" method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="category_id" value="<?= e((string) $item['category_id']) ?>">
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
