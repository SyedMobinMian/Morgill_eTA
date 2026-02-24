<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function currentAdmin(): ?array {
    // Session me admin id na ho to login nahi samjha jayega.
    if (empty($_SESSION['admin_user_id'])) {
        return null;
    }

    // Ek request ke andar repeated DB hit bachane ke liye cache.
    static $admin = null;
    if ($admin !== null) {
        return $admin;
    }

    // Session wali id ka active admin record load karo.
    $stmt = adminDB()->prepare('SELECT id, username, email, role, is_active FROM admin_users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int)$_SESSION['admin_user_id']]);
    $row = $stmt->fetch();

    // Invalid/inactive record mile to force logout.
    if (!$row || (int)$row['is_active'] !== 1) {
        adminLogout();
        return null;
    }

    $admin = $row;
    return $admin;
}

function isAdminLoggedIn(): bool {
    // Single source of truth for login check.
    return currentAdmin() !== null;
}

function currentAdminRole(): string {
    $admin = currentAdmin();
    if (!$admin) {
        return 'staff';
    }
    $role = strtolower((string)($admin['role'] ?? 'staff'));
    return in_array($role, ['master', 'admin', 'staff'], true) ? $role : 'staff';
}

function requireRole(array $roles): void {
    if (!isAdminLoggedIn()) {
        flash('error', 'Please login to continue.');
        redirectTo(baseUrl('login.php'));
    }
    $role = currentAdminRole();
    if (!in_array($role, $roles, true)) {
        flash('error', 'You do not have permission to perform this action.');
        redirectTo(baseUrl('dashboard.php'));
    }
}

function canManageRecords(): bool {
    return currentAdminRole() === 'master';
}

function canCreateRecords(): bool {
    return in_array(currentAdminRole(), ['master', 'admin'], true);
}

function requireAdmin(): void {
    // Protected page par guest ko login par bhejo.
    if (!isAdminLoggedIn()) {
        flash('error', 'Please login to continue.');
        redirectTo(baseUrl('login.php'));
    }
}

function adminLogin(string $username, string $password): bool {
    // Input normalize + empty guard.
    $username = sanitizeText($username, 60);
    if ($username === '' || $password === '') {
        return false;
    }

    // Username se admin record fetch karo.
    $stmt = adminDB()->prepare('SELECT id, password_hash, is_active FROM admin_users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch();

    // Inactive ya missing user reject.
    if (!$row || (int)$row['is_active'] !== 1) {
        return false;
    }

    // Password hash verify.
    if (!password_verify($password, $row['password_hash'])) {
        return false;
    }

    // Login success par session id regenerate karo (session fixation protection).
    session_regenerate_id(true);
    $_SESSION['admin_user_id'] = (int)$row['id'];
    return true;
}

function adminLogout(): void {
    // Admin session clear karo aur nayi session id banao.
    unset($_SESSION['admin_user_id']);
    session_regenerate_id(true);
}
