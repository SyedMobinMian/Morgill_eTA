<?php
/**
 * backend/config.php
 * Markazi configuration + shared helpers.
 *
 * Security model:
 * - Secrets environment variables (aur optional .env file) se load hote hain.
 * - Sensitive password/API key yahan hardcode nahi honi chahiye.
 */

/**
 * Halki phulki .env loader function taake credentials source me hardcode na hon.
 * Format:
 *   KEY=value
 *   # comment likh sakte ho
 */
function loadEnvFile(string $path): void {
    // Agar .env file na ho to chup chaap skip karo.
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        // Khali lines aur comments ignore karo.
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Sirf KEY=value format accept karo.
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $val = trim($parts[1]);
        // Ghalat/malformed key entries ko ignore karo.
        if ($key === '') {
            continue;
        }

        // Agar value quotes me ho to outer quotes hata do.
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            $val = substr($val, 1, -1);
        }

        // Pehle se set server env ko overwrite na karo.
        if (getenv($key) === false) {
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

function env(string $key, string $default = ''): string {
    // Sab se pehle process env se value parho.
    $val = getenv($key);
    if ($val !== false) {
        return (string)$val;
    }
    // Shared hosting/runtime differences ke liye fallback checks.
    if (isset($_ENV[$key])) {
        return (string)$_ENV[$key];
    }
    if (isset($_SERVER[$key])) {
        return (string)$_SERVER[$key];
    }
    return $default;
}

function envBool(string $key, bool $default = false): bool {
    // Common truthy strings ko boolean me convert karo.
    $raw = strtolower(trim(env($key, $default ? '1' : '0')));
    return in_array($raw, ['1', 'true', 'yes', 'on'], true);
}

// Root par optional .env (project/.env) load karo.
loadEnvFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');

// Environment detect karo: APP_ENV ko priority, warna localhost fallback.
$appEnv = strtolower(env('APP_ENV', ''));
$isLocal = $appEnv !== ''
    ? in_array($appEnv, ['local', 'development', 'dev'], true)
    : (
        (($_SERVER['SERVER_NAME'] ?? '') === 'localhost') ||
        (($_SERVER['SERVER_NAME'] ?? '') === '127.0.0.1')
    );

// Database ki configuration.
define('DB_HOST', env('DB_HOST', $isLocal ? 'localhost' : ''));
define('DB_NAME', env('DB_NAME', $isLocal ? 'dcform_db' : ''));
define('DB_USER', env('DB_USER', $isLocal ? 'root' : ''));
define('DB_PASS', env('DB_PASS', $isLocal ? '' : ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Merchant Warrior payment ki settings.
define('MW_CLIENT_ID', env('MW_CLIENT_ID', ''));
define('MW_CLIENT_SECRET', env('MW_CLIENT_SECRET', ''));
define('MW_MMID', env('MW_MMID', ''));
define('MW_API_BASE', env('MW_API_BASE', 'https://base.merchantwarrior.com/post/'));
define('MW_PAYFRAME_JS', env('MW_PAYFRAME_JS', 'https://securetest.merchantwarrior.com/payframe/payframe.js'));
define('MW_PAYFRAME_BASE', env('MW_PAYFRAME_BASE', 'https://securetest.merchantwarrior.com/payframe/'));

// Razorpay values backward compatibility ke liye rakhe gaye hain.
define('RAZORPAY_KEY_ID', env('RAZORPAY_KEY_ID', ''));
define('RAZORPAY_KEY_SECRET', env('RAZORPAY_KEY_SECRET', ''));

// Application aur email identity ki settings.
function detectAppUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $appRoot = realpath(dirname(__DIR__));
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string)$_SERVER['DOCUMENT_ROOT']) : false;

    if ($appRoot && $docRoot) {
        $appNorm = str_replace('\\', '/', (string)$appRoot);
        $docNorm = rtrim(str_replace('\\', '/', (string)$docRoot), '/');

        if (str_starts_with($appNorm, $docNorm)) {
            $subPath = trim(substr($appNorm, strlen($docNorm)), '/');
            return rtrim($scheme . '://' . $host . ($subPath !== '' ? '/' . $subPath : ''), '/');
        }
    }

    return rtrim($scheme . '://' . $host, '/');
}
define('ADMIN_EMAIL', env('ADMIN_EMAIL', 'admin@yourdomain.com'));
define('FROM_EMAIL', env('FROM_EMAIL', 'noreply@yourdomain.com'));
define('FROM_NAME', env('FROM_NAME', 'dcForm Application'));
define('APP_URL', rtrim(env('APP_URL', detectAppUrl()), '/'));
define('EMAIL_BCC_ADMIN', envBool('EMAIL_BCC_ADMIN', false));

// SMTP credentials aur transport ki settings.
define('SMTP_HOST', env('SMTP_HOST', ''));
define('SMTP_PORT', (int)env('SMTP_PORT', '587'));
define('SMTP_SECURE', strtolower(env('SMTP_SECURE', 'tls'))); // tls ya ssl
define('SMTP_USERNAME', env('SMTP_USERNAME', ''));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', ''));

// Doosre runtime constants.
define('ETA_FEE', (int)env('ETA_FEE', '7900'));
// Ye sirf first-time bootstrap me use hota hai jab admin_users table khali ho.
define('ADMIN_SEED_PASSWORD', env('ADMIN_SEED_PASSWORD', 'change_me_in_env'));

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Kolkata'));

/**
 * PDO connection factory (ek hi shared instance rakhta hai).
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed.',
            ]));
        }
    }
    return $pdo;
}

/**
 * Standard JSON response helper function.
 */
function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

function cleanAlpha(string $val): string {
    return preg_replace('/[^a-zA-Z\s\-]/', '', trim($val));
}

function cleanAlphaNum(string $val): string {
    return preg_replace('/[^a-zA-Z0-9\s\-]/', '', trim($val));
}

/**
 * Storage ke liye input cleaner.
 * Note: render time par output ko phir bhi escape karna zaroori hai.
 */
function clean(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function sanitize(string $value): string {
    return clean($value);
}

function generateReference(): string {
    return 'ETA-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('Ymd');
}

function csrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

