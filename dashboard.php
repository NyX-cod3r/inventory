<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_login();

$productStatement = $pdo->prepare('SELECT COUNT(*) FROM Product');
$productStatement->execute();
$totalProducts = (int) $productStatement->fetchColumn();

$valueStatement = $pdo->prepare('SELECT COALESCE(SUM(inventory_value), 0) FROM v_inventory_summary');
$valueStatement->execute();
$totalStockValue = (float) $valueStatement->fetchColumn();

$lowStockStatement = $pdo->prepare('SELECT COUNT(*) FROM v_low_stock');
$lowStockStatement->execute();
$lowStockCount = (int) $lowStockStatement->fetchColumn();

$page_title = 'Dashboard | Inventory Manager';
require __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">Dashboard</h1>
<p class="page-subtitle">A quick view of your current inventory position.</p>
<section class="card-grid">
    <article class="card">
        <p class="metric-label">Total Products</p>
        <p class="metric-value"><?= e((string) $totalProducts) ?></p>
    </article>
    <article class="card">
        <p class="metric-label">Total Stock Value</p>
        <p class="metric-value">₹<?= e(number_format($totalStockValue, 2)) ?></p>
    </article>
    <article class="card">
        <p class="metric-label">Low-Stock Records</p>
        <p class="metric-value"><?= e((string) $lowStockCount) ?></p>
    </article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
