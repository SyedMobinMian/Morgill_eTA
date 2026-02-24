<?php

declare(strict_types=1);

session_start();

if (!empty($_SESSION['admin_user_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}

header('Location: admin/login.php');
exit;
