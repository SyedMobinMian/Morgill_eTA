<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function renderAdminLayoutStart(string $title, string $active): void {
    // Header/sidebar render se pehle current admin + flash message lo.
    $admin = currentAdmin();
    $flash = consumeFlash();
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= esc($title) ?> | Admin</title>
    <link rel="stylesheet" href="<?= esc(assetUrl('css/admin.css')) ?>">
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar">
        <h2>dcForm Admin</h2>
        <nav>
            <!-- Active menu item current page ke mutabiq highlight hota hai -->
            <a class="<?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= esc(baseUrl('dashboard.php')) ?>">Dashboard</a>
            <a class="<?= $active === 'users' ? 'active' : '' ?>" href="<?= esc(baseUrl('users.php')) ?>">Users / Reports</a>
            <a class="<?= $active === 'documents' ? 'active' : '' ?>" href="<?= esc(baseUrl('documents.php')) ?>">Documents</a>
            <a class="<?= $active === 'forms' ? 'active' : '' ?>" href="<?= esc(baseUrl('form-links.php')) ?>">Forms</a>
            <a class="<?= $active === 'email' ? 'active' : '' ?>" href="<?= esc(baseUrl('email.php')) ?>">Email</a>
            <a class="<?= $active === 'settings' ? 'active' : '' ?>" href="<?= esc(baseUrl('settings.php')) ?>">Settings</a>
        </nav>
    </aside>
    <main>
        <header class="topbar">
            <div><?= esc($title) ?></div>
            <div class="topbar-right">
                <span><?= esc($admin['username'] ?? 'Admin') ?></span>
                <a href="<?= esc(baseUrl('logout.php')) ?>">Logout</a>
            </div>
        </header>
        <?php if ($flash): ?>
            <!-- One-time flash message -->
            <div class="flash <?= esc($flash['type']) ?>"><?= esc($flash['message']) ?></div>
        <?php endif; ?>
        <section class="content">
    <?php
}

function renderAdminLayoutEnd(): void {
    // Layout wrappers close + footer render.
    ?>
        </section>
        <footer class="footer">&copy; <?= date('Y') ?> dcForm Admin</footer>
    </main>
</div>
</body>
</html>
    <?php
}
