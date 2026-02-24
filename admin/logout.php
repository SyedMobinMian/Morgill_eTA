<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Session se admin logout karo aur login page par bhejo.
adminLogout();
flash('success', 'Logged out successfully.');
redirectTo(baseUrl('login.php'));
