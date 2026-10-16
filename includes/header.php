<?php

$title = $page_title ?? 'Inventory Manager';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="nav-wrap">
            <a class="brand" href="dashboard.php">Inventory Manager</a>
            <?php if ($user !== null): ?>
                <nav class="main-nav" aria-label="Main navigation">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="categories.php">Categories</a>
                    <a href="suppliers.php">Suppliers</a>
                    <a href="products.php">Products</a>
                    <a href="warehouses.php">Warehouses</a>
                    <a href="inventory.php">Inventory</a>
                    <a href="movements.php">Movements</a>
                    <a href="reports.php">Reports</a>
                </nav>
                <div class="user-menu">
                    <span><?= e($user['username']) ?> (<?= e($user['role']) ?>)</span>
                    <form action="logout.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <button class="link-button" type="submit">Logout</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </header>
    <main class="page-container">
