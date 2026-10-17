<?php

if (!is_file(__DIR__ . '/config.php')) {
    http_response_code(500);
    echo 'Database configuration is missing. Copy config/config.sample.php to '
        . 'config/config.php and set your MySQL credentials. See docs/README.md (or the README on GitHub) for setup steps.';
    exit;
}

$config = require __DIR__ . '/config.php';

$dsn = 'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4';

$pdo = new PDO($dsn, $config['db_user'], $config['db_password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
