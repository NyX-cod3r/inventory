<?php

$title = $page_title ?? 'Inventory Manager';
$user = current_user();

require_once __DIR__ . '/icons.php';

$current = basename($_SERVER['SCRIPT_NAME'] ?? '');

$navItems = [
    'dashboard.php'  => ['Dashboard', 'dashboard'],
    'categories.php' => ['Categories', 'categories'],
    'suppliers.php'  => ['Suppliers', 'suppliers'],
    'products.php'   => ['Products', 'products'],
    'warehouses.php' => ['Warehouses', 'warehouses'],
    'inventory.php'  => ['Inventory', 'inventory'],
    'movements.php'  => ['Movements', 'movements'],
    'reports.php'    => ['Reports', 'reports'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/liquidGlass.css">
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="topbar">
        <div class="topbar__inner">
            <a class="brand" href="dashboard.php"><?= icon('brand', 22) ?><span>Inventory Manager</span></a>
            <?php if ($user !== null): ?>
                <div class="user-menu">
                    <span class="user-menu__avatar"><?= icon('user', 16) ?></span>
                    <span class="user-menu__meta">
                        <strong><?= e($user['username']) ?></strong>
                        <em><?= e($user['role']) ?></em>
                    </span>
                    <form action="logout.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <button class="link-button" type="submit"><?= icon('logout', 16) ?><span>Logout</span></button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($user !== null): ?>
        <nav class="rail" aria-label="Main navigation">
            <div class="rail__inner">
                <?php foreach ($navItems as $href => [$label, $iconName]): ?>
                    <a class="rail__item<?= $current === $href ? ' is-active' : '' ?>"
                       href="<?= e($href) ?>"<?= $current === $href ? ' aria-current="page"' : '' ?>>
                        <?= icon($iconName, 18) ?><span><?= e($label) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
    <?php endif; ?>

    <main id="main" class="page">
