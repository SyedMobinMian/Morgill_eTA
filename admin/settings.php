<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requireAdmin();

$db = adminDB();
$admin = currentAdmin();
$canManage = canManageRecords();

// Settings form submit par admin profile/password update hogi.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canManage) {
        flash('error', 'Only MasterAdmin can update admin settings.');
        redirectTo(baseUrl('settings.php'));
    }
    $csrf = (string)($_POST['csrf_token'] ?? '');
    // CSRF protection.
    if (!verifyCsrf($csrf)) {
        flash('error', 'Invalid request token.');
        redirectTo(baseUrl('settings.php'));
    }

    $username = sanitizeText($_POST['username'] ?? '', 60);
    $email = sanitizeEmail($_POST['email'] ?? '');
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');

    // Basic required fields validate karo.
    if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Valid username and email are required.');
        redirectTo(baseUrl('settings.php'));
    }

    // Update allow karne se pehle current password confirm karo.
    $check = $db->prepare('SELECT password_hash FROM admin_users WHERE id = :id');
    $check->execute([':id' => $admin['id']]);
    $hash = (string)$check->fetchColumn();

    if (!password_verify($currentPassword, $hash)) {
        flash('error', 'Current password is incorrect.');
        redirectTo(baseUrl('settings.php'));
    }

    $params = [':id' => $admin['id'], ':username' => $username, ':email' => $email];
    $sql = 'UPDATE admin_users SET username = :username, email = :email';

    // Naya password diya ho to hash karke update me add karo.
    if ($newPassword !== '') {
        if (strlen($newPassword) < 8) {
            flash('error', 'New password must be at least 8 characters.');
            redirectTo(baseUrl('settings.php'));
        }
        $sql .= ', password_hash = :password_hash';
        $params[':password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    $sql .= ' WHERE id = :id';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    flash('success', 'Admin credentials updated.');
    redirectTo(baseUrl('settings.php'));
}

renderAdminLayoutStart('Settings', 'settings');
?>
<form method="post" class="panel" autocomplete="off">
    <h3>Update Admin Credentials</h3>

    <label>Username</label>
    <input type="text" name="username" required maxlength="60" value="<?= esc($admin['username']) ?>" <?= $canManage ? '' : 'disabled' ?>>

    <label>Email</label>
    <input type="email" name="email" required maxlength="255" value="<?= esc($admin['email']) ?>" <?= $canManage ? '' : 'disabled' ?>>

    <label>Current Password</label>
    <input type="password" name="current_password" required <?= $canManage ? '' : 'disabled' ?>>

    <label>New Password (optional)</label>
    <input type="password" name="new_password" minlength="8" <?= $canManage ? '' : 'disabled' ?>>

    <?php if ($canManage): ?>
        <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
        <button type="submit">Save Settings</button>
    <?php else: ?>
        <p style="color:var(--muted);">Read-only access (MasterAdmin required).</p>
    <?php endif; ?>
</form>
<?php renderAdminLayoutEnd(); ?>
