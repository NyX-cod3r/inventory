<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$statement = $pdo->prepare('SELECT COUNT(*) FROM Users');
$statement->execute();
$userCount = (int) $statement->fetchColumn();

header('Location: ' . ($userCount === 0 ? 'setup.php' : 'login.php'));
exit;
