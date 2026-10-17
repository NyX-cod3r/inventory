<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$countStatement = $pdo->prepare('SELECT COUNT(*) FROM Users');
$countStatement->execute();
if ((int) $countStatement->fetchColumn() === 0) {
    header('Location: setup.php');
    exit;
}

if (current_user() !== null) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {
        $statement = $pdo->prepare('SELECT user_id, username, password_hash, role FROM Users WHERE username = ? LIMIT 1');
        $statement->execute([$username]);
        $user = $statement->fetch();

        if ($user !== false && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'user_id' => (int) $user['user_id'],
                'username' => $user['username'],
                'role' => $user['role'],
            ];
            header('Location: dashboard.php');
            exit;
        }

        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Inventory Manager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <section class="card login-card">
        <h1 class="page-title">Inventory Manager</h1>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" data-validate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" maxlength="100" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>
            <button class="button" type="submit">Login</button>
        </form>
    </section>
    <script src="assets/js/app.js"></script>
</body>
</html>
