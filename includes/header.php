<?php

$title = $page_title ?? 'Inventory Manager';
$user = current_user();

require_once __DIR__ . '/icons.php';

$current = basename($_SERVER['SCRIPT_NAME'] ?? '');

$navItems = [
    'dashboard.php'  => ['Dashboard', 'dashboard'],
    'products.php'   => ['Products', 'products'],
    'inventory.php'  => ['Inventory', 'inventory'],
    'movements.php'  => ['Movements', 'movements'],
    'categories.php' => ['Categories', 'categories'],
    'suppliers.php'  => ['Suppliers', 'suppliers'],
    'warehouses.php' => ['Warehouses', 'warehouses'],
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
    <script>
        /* Runs before first paint so the sidebar never flashes the wrong width. */
        try {
            var narrow = window.matchMedia('(max-width: 900px)').matches;
            if (!narrow && localStorage.getItem('im-sidebar') !== 'collapsed') {
                document.documentElement.classList.add('sidebar-open');
            }
        } catch (e) {}
    </script>
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <div class="workspace">
        <?php if ($user !== null): ?>
            <aside class="sidebar" id="sidebar">
                <nav class="sidebar__nav" aria-label="Main navigation">
                    <?php foreach ($navItems as $href => [$label, $iconName]): ?>
                        <a class="sidebar__item<?= $current === $href ? ' is-active' : '' ?>"
                           href="<?= e($href) ?>" title="<?= e($label) ?>"
                           <?= $current === $href ? 'aria-current="page"' : '' ?>>
                            <?= icon($iconName, 19) ?><span class="sidebar__label"><?= e($label) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>
            <button class="sidebar-scrim" type="button" id="sidebar-scrim" tabindex="-1" aria-label="Close navigation"></button>
        <?php endif; ?>

        <div class="content">
            <header class="topbar">
                <div class="topbar__inner">
                    <?php if ($user !== null): ?>
                        <button class="sidebar-toggle" type="button" id="sidebar-toggle"
                                aria-expanded="true" aria-controls="sidebar" aria-label="Toggle navigation">
                            <?= icon('menu', 18) ?>
                        </button>
                    <?php endif; ?>
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

            <main id="main" class="page">
