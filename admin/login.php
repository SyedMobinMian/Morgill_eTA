<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Agar session active hai to login page ki zarurat nahi.
if (isAdminLoggedIn()) {
    redirectTo(baseUrl('dashboard.php'));
}

// Login form submit hone par credentials verify karo.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeText($_POST['username'] ?? '', 60);
    $password = (string)($_POST['password'] ?? '');
    $csrf = (string)($_POST['csrf_token'] ?? '');

    // CSRF fail ho to request reject.
    if (!verifyCsrf($csrf)) {
        flash('error', 'Invalid request token.');
        redirectTo(baseUrl('login.php'));
    }

    // Valid login par dashboard, warna error flash.
    if (adminLogin($username, $password)) {
        flash('success', 'Welcome back.');
        redirectTo(baseUrl('dashboard.php'));
    }

    flash('error', 'Invalid username or password.');
    redirectTo(baseUrl('login.php'));
}

$flash = consumeFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login</title>
    <link rel="stylesheet" href="<?= esc(assetUrl('css/admin.css')) ?>">
</head>
<body class="login-page">
    <form method="post" class="login-box" autocomplete="off">
        <h1>Admin Login</h1>
        <?php if ($flash): ?>
            <div class="flash <?= esc($flash['type']) ?>"><?= esc($flash['message']) ?></div>
        <?php endif; ?>
        <label>Username</label>
        <input type="text" name="username" required maxlength="60">

        <label>Password</label>
        <input type="password" name="password" required maxlength="120">

        <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
        <button type="submit">Login</button>
        <small>Use your configured admin credentials.</small>
    </form>
</body>
</html>
