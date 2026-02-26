<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function adminDB(): PDO {
    // Shared DB connection reuse karo aur one-time admin bootstrap checks chalao.
    static $ready = false;
    $db = getDB();

    if (!$ready) {
        ensureAdminTables($db);
        $ready = true;
    }

    return $db;
}

function ensureAdminTables(PDO $db): void {
    // Admin credentials + role-based access table.
    $db->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(60) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('master','admin','staff') NOT NULL DEFAULT 'staff',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Har traveller ke secure form links ke liye token table.
    $db->exec("CREATE TABLE IF NOT EXISTS form_access_tokens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        traveller_id INT UNSIGNED NOT NULL,
        form_number VARCHAR(30) NOT NULL UNIQUE,
        token VARCHAR(80) NOT NULL UNIQUE,
        form_country ENUM('Canada','Vietnam','UK') NOT NULL DEFAULT 'Canada',
        email_sent_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_form_token_traveller FOREIGN KEY (traveller_id) REFERENCES travellers(id) ON DELETE CASCADE,
        INDEX idx_form_token_traveller (traveller_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");


    // Paid applications ke uploaded/generated documents ki table.
    $db->exec("CREATE TABLE IF NOT EXISTS payment_documents (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        application_id INT UNSIGNED NOT NULL,
        payment_id VARCHAR(100) NOT NULL,
        reference VARCHAR(50) NOT NULL,
        receipt_file VARCHAR(255) NOT NULL,
        form_pdf_file VARCHAR(255) NOT NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        currency VARCHAR(10) NOT NULL DEFAULT 'INR',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_payment_docs_app (application_id),
        INDEX idx_payment_docs_reference (reference),
        CONSTRAINT fk_payment_docs_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Admin se trigger hone wali emails ka audit trail.
    $db->exec("CREATE TABLE IF NOT EXISTS admin_email_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        traveller_id INT UNSIGNED NOT NULL,
        recipient_email VARCHAR(255) NOT NULL,
        subject_line VARCHAR(255) NOT NULL,
        send_status ENUM('sent','failed') NOT NULL,
        error_message VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_admin_email_traveller FOREIGN KEY (traveller_id) REFERENCES travellers(id) ON DELETE CASCADE,
        INDEX idx_admin_email_traveller (traveller_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Sirf tab first admin seed karo jab table bilkul khali ho.
    $count = (int)$db->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    if ($count === 0) {
        $stmt = $db->prepare('INSERT INTO admin_users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)');
        $stmt->execute([
            ':username' => 'admin',
            ':email' => ADMIN_EMAIL,
            // Initial password env se aata hai, first login ke baad change karna chahiye.
            ':password_hash' => password_hash(ADMIN_SEED_PASSWORD, PASSWORD_DEFAULT),
            ':role' => 'master',
        ]);
    }
}

function esc(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function flash(string $type, string $message): void {
    // Session me one-time UI message store karo.
    $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
}

function consumeFlash(): ?array {
    // Flash read karo aur clear karo taake bar bar show na ho.
    if (empty($_SESSION['admin_flash'])) {
        return null;
    }
    $f = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
    return $f;
}

function sanitizeEmail(string $value): string {
    return filter_var(trim($value), FILTER_SANITIZE_EMAIL) ?: '';
}

function sanitizeText(string $value, int $maxLen = 255): string {
    // Basic server-side text cleanup aur length limit.
    $value = trim(strip_tags($value));
    if ($maxLen > 0) {
        $value = mb_substr($value, 0, $maxLen);
    }
    return $value;
}

function redirectTo(string $path): void {
    // Redirect ka markazi helper.
    header('Location: ' . $path);
    exit;
}

function baseUrl(string $path = ''): string {
    // Admin URLs hamesha /admin root ke andar banengi.
    $base = rtrim(APP_URL, '/') . '/admin';
    return $path === '' ? $base : $base . '/' . ltrim($path, '/');
}

function assetUrl(string $path = ''): string {
    // Shared assets hamesha /assets root se aayengi.
    $base = rtrim(APP_URL, '/') . '/assets';
    return $path === '' ? $base : $base . '/' . ltrim($path, '/');
}

function buildFormNumber(): string {
    // Admin/user ko dikhane ke liye readable form reference banao.
    return 'FRM-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function buildFormToken(): string {
    // Link URL ke andar use hone wala secure random token.
    return bin2hex(random_bytes(24));
}

function getOrCreateFormAccess(PDO $db, int $travellerId, string $country): array {
    // Agar token pehle se ho to same traveller ka wohi token reuse karo.
    $stmt = $db->prepare('SELECT * FROM form_access_tokens WHERE traveller_id = :traveller_id LIMIT 1');
    $stmt->execute([':traveller_id' => $travellerId]);
    $tokenRow = $stmt->fetch();

    if ($tokenRow) {
        if ($tokenRow['form_country'] !== $country) {
            // Admin country/form type badle to DB me bhi sync karo.
            $up = $db->prepare('UPDATE form_access_tokens SET form_country = :form_country WHERE id = :id');
            $up->execute([':form_country' => $country, ':id' => $tokenRow['id']]);
            $tokenRow['form_country'] = $country;
        }
        return $tokenRow;
    }

    // Unique form number banao aur DB me uniqueness check karo.
    do {
        $formNumber = buildFormNumber();
        $exists = $db->prepare('SELECT COUNT(*) FROM form_access_tokens WHERE form_number = :form_number');
        $exists->execute([':form_number' => $formNumber]);
    } while ((int)$exists->fetchColumn() > 0);

    // Naya token row insert karo aur saved record return karo.
    $token = buildFormToken();
    $ins = $db->prepare('INSERT INTO form_access_tokens (traveller_id, form_number, token, form_country) VALUES (:traveller_id, :form_number, :token, :form_country)');
    $ins->execute([
        ':traveller_id' => $travellerId,
        ':form_number' => $formNumber,
        ':token' => $token,
        ':form_country' => $country,
    ]);

    $id = (int)$db->lastInsertId();
    $fetch = $db->prepare('SELECT * FROM form_access_tokens WHERE id = :id');
    $fetch->execute([':id' => $id]);
    return (array)$fetch->fetch();
}


