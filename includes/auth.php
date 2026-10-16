<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (current_user() === null) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();

    if (current_user()['role'] !== 'admin') {
        http_response_code(403);
        exit('Admin access is required.');
    }
}
