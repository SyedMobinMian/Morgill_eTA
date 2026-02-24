<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Admin already login ho to direct dashboard par bhejo.
if (isAdminLoggedIn()) {
    redirectTo(baseUrl('dashboard.php'));
}
// Warna login page par redirect.
redirectTo(baseUrl('login.php'));
