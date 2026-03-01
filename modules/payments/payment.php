<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../forms/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    jsonResponse(false, 'Security token invalid. Please refresh and try again.');
}

if (empty($_SESSION['application_id'])) {
    jsonResponse(false, 'Session expired. Please start your application again.');
}

if (!defined('MW_CLIENT_ID') || MW_CLIENT_ID === '' || !defined('MW_CLIENT_SECRET') || MW_CLIENT_SECRET === '' || !defined('MW_MMID') || MW_MMID === '') {
    jsonResponse(false, 'Payment gateway is not configured. Please contact support.');
}

$applicationId = (int) $_SESSION['application_id'];
$plan = clean($_POST['plan'] ?? $_SESSION['plan'] ?? 'standard');
if (!in_array($plan, ['standard', 'priority'], true)) {
    $plan = 'standard';
}
$_SESSION['plan'] = $plan;

$errors = [];
$firstName = trim((string)($_POST['billing_first_name'] ?? ''));
$lastName = trim((string)($_POST['billing_last_name'] ?? ''));
$address = trim((string)($_POST['billing_address'] ?? ''));
$city = trim((string)($_POST['billing_city'] ?? ''));
$zip = trim((string)($_POST['billing_zip'] ?? ''));
$country = trim((string)($_POST['billing_country'] ?? ''));
$state = trim((string)($_POST['billing_state'] ?? ''));
$email = trim((string)($_POST['billing_email'] ?? ''));

if ($err = validateName($firstName, 'First Name')) $errors['billing_first_name'] = $err;
if ($err = validateName($lastName, 'Last Name')) $errors['billing_last_name'] = $err;
if ($err = validateRequired($address, 'Billing Address')) $errors['billing_address'] = $err;
if ($err = validateRequired($city, 'City')) $errors['billing_city'] = $err;
if ($err = validatePostalCode($zip)) $errors['billing_zip'] = $err;
if ($err = validateSelect($country, 'Country')) $errors['billing_country'] = $err;
if ($email !== '' && ($err = validateEmail($email))) $errors['billing_email'] = $err;

if (!empty($errors)) {
    jsonResponse(false, 'Please fill in all billing fields.', ['errors' => $errors]);
}

$db = getDB();
$stmt = $db->prepare('SELECT total_travellers FROM applications WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $applicationId]);
$app = $stmt->fetch();

if (!$app) {
    jsonResponse(false, 'Application not found. Please start again.');
}

$feePerPerson = ETA_FEE;
$totalAmountMinor = $feePerPerson * max(1, (int)$app['total_travellers']);
if ($plan === 'priority') {
    $totalAmountMinor = (int)($totalAmountMinor * 1.5);
}

$currency = 'USD';
$totalAmountMajor = number_format($totalAmountMinor / 100, 2, '.', '');
$orderId = 'MW-' . $applicationId . '-' . time() . '-' . bin2hex(random_bytes(3));

// Note: Inserting Merchant Warrior Order ID into 'razorpay_order_id' column to maintain DB compatibility.
$stmt = $db->prepare(
    "INSERT INTO payments
        (application_id, razorpay_order_id, amount, currency, plan,
         billing_first_name, billing_last_name, billing_email,
         billing_address, billing_country, billing_state, billing_city, billing_zip, status)
     VALUES
        (:app_id, :order_id, :amount, :currency, :plan,
         :bfn, :bln, :bem,
         :badr, :bco, :bst, :bci, :bzi, 'created')"
);
$stmt->execute([
    ':app_id' => $applicationId,
    ':order_id' => $orderId,
    ':amount' => $totalAmountMajor,
    ':currency' => $currency,
    ':plan' => $plan,
    ':bfn' => clean($firstName),
    ':bln' => clean($lastName),
    ':bem' => clean($email),
    ':badr' => clean($address),
    ':bco' => clean($country),
    ':bst' => clean($state),
    ':bci' => clean($city),
    ':bzi' => clean($zip),
]);

$_SESSION['payment_order_id'] = $orderId;

$apiBase = trim((string)MW_API_BASE);
$parts = parse_url($apiBase);
$scheme = $parts['scheme'] ?? 'https';
$host = $parts['host'] ?? 'base.merchantwarrior.com';
$port = isset($parts['port']) ? ':' . (int)$parts['port'] : '';
$payframeSubmitUrl = $scheme . '://' . $host . $port . '/payframe/';

jsonResponse(true, 'Payment initialised.', [
    'order_id' => $orderId,
    'amount' => $totalAmountMajor,
    'currency' => $currency,
    'merchant_uuid' => MW_CLIENT_ID,
    'api_key' => MW_CLIENT_SECRET,
    'payframe_js' => MW_PAYFRAME_JS,
    'payframe_src' => MW_PAYFRAME_BASE,
    'submit_url' => $payframeSubmitUrl,
    'reference' => $_SESSION['application_ref'] ?? '',
]);
