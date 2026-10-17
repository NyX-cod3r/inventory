<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$countStatement = $pdo->prepare('SELECT COUNT(*) FROM Users');
$countStatement->execute();
if ((int) $countStatement->fetchColumn() > 0) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (preg_match('/^[A-Za-z0-9_.-]{3,100}$/', $username) !== 1) {
        $error = 'Use 3 to 100 letters, numbers, dots, underscores, or hyphens for the username.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $insertStatement = $pdo->prepare('INSERT INTO Users (username, password_hash, role) VALUES (?, ?, ?)');
            $insertStatement->execute([$username, password_hash($password, PASSWORD_DEFAULT), 'admin']);
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'user_id' => (int) $pdo->lastInsertId(),
                'username' => $username,
                'role' => 'admin',
            ];
            header('Location: dashboard.php');
            exit;
        } catch (PDOException $exception) {
            $error = 'This username is already in use.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial Setup | Inventory Manager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <section class="card login-card">
            <h1 class="page-title">Create Administrator</h1>
            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" data-validate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="form-group">
                    <label for="username">Admin Username</label>
                    <input id="username" name="username" type="text" maxlength="100" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" minlength="8" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" minlength="8" required>
                </div>
                <button class="button" type="submit">Create Administrator</button>
            </form>
    </section>
    <script src="assets/js/app.js"></script>
</body>
</html>
