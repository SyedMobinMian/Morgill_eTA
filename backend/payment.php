<?php
// ============================================================
//  backend/payment.php — Create Razorpay Order
//  POST se aata hai form.js initiatePayment()
// ============================================================
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

// ── Session check ─────────────────────────────────────────
if (empty($_SESSION['application_id'])) {
    jsonResponse(false, 'Session expired. Please start your application again.');
}

$applicationId = (int) $_SESSION['application_id'];

// ── Plan select karo (card-confirm se aata hai) ───────────
$plan = clean($_POST['plan'] ?? $_SESSION['plan'] ?? 'standard');
if (!in_array($plan, ['standard','priority'])) $plan = 'standard';
$_SESSION['plan'] = $plan;

// ── Billing fields validate karo ──────────────────────────
$errors = [];
$billingFields = [
    'billing_first_name' => 'First Name',
    'billing_last_name'  => 'Last Name',
    'billing_address'    => 'Billing Address',
    'billing_city'       => 'City',
    'billing_zip'        => 'Zip / Postal Code',
];
foreach ($billingFields as $field => $label) {
    $v = trim($_POST[$field] ?? '');
    if ($v === '') $errors[$field] = "$label is required.";
}
if (!empty($_POST['billing_email'])) {
    if ($err = validateEmail(trim($_POST['billing_email']))) {
        $errors['billing_email'] = $err;
    }
}
if (!empty($errors)) {
    jsonResponse(false, 'Please fill in all billing fields.', ['errors' => $errors]);
}

// ── Total amount calculate karo ───────────────────────────
$db   = getDB();
$stmt = $db->prepare("SELECT total_travellers FROM applications WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $applicationId]);
$app  = $stmt->fetch();

if (!$app) {
    jsonResponse(false, 'Application not found. Please start again.');
}

$feePerPerson = ETA_FEE; // config.php mein define hai (paise mein, e.g. 7900 = ₹79)
$totalAmount  = $feePerPerson * max(1, (int)$app['total_travellers']);
if ($plan === 'priority') {
    $totalAmount = (int)($totalAmount * 1.5); // +50% priority surcharge
}

// ── Razorpay Order create karo ────────────────────────────
$orderData = [
    'amount'          => $totalAmount,
    'currency'        => 'INR',
    'receipt'         => $_SESSION['application_ref'] ?? 'ETA-' . $applicationId,
    'payment_capture' => 1,
    'notes'           => [
        'application_id' => $applicationId,
        'reference'      => $_SESSION['application_ref'] ?? '',
        'plan'           => $plan,
    ],
];

$curl = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($orderData),
    CURLOPT_USERPWD        => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 30,
]);
$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($curl);
curl_close($curl);

if ($curlErr) {
    error_log('Razorpay CURL error: ' . $curlErr);
    jsonResponse(false, 'Could not connect to payment gateway. Please try again.');
}

$order = json_decode($response, true);
if ($httpCode !== 200 || empty($order['id'])) {
    error_log('Razorpay order failed: ' . $response);
    jsonResponse(false, 'Payment gateway error. Please try again later.');
}

// ── Payment record DB mein save karo ─────────────────────
// Pehle check karo — same order already exists?
$chk = $db->prepare("SELECT id FROM payments WHERE application_id=:aid AND razorpay_order_id=:oid LIMIT 1");
$chk->execute([':aid' => $applicationId, ':oid' => $order['id']]);
if (!$chk->fetch()) {
    $stmt = $db->prepare("
        INSERT INTO payments
            (application_id, razorpay_order_id, amount, currency, plan,
             billing_first_name, billing_last_name, billing_email,
             billing_address, billing_country, billing_state, billing_city, billing_zip)
        VALUES
            (:app_id, :order_id, :amount, 'INR', :plan,
             :bfn, :bln, :bem,
             :badr, :bco, :bst, :bci, :bzi)
    ");
    $stmt->execute([
        ':app_id'   => $applicationId,
        ':order_id' => $order['id'],
        ':amount'   => $totalAmount / 100, // paise → rupees
        ':plan'     => $plan,
        ':bfn'      => clean($_POST['billing_first_name'] ?? ''),
        ':bln'      => clean($_POST['billing_last_name']  ?? ''),
        ':bem'      => clean($_POST['billing_email']      ?? ''),
        ':badr'     => clean($_POST['billing_address']    ?? ''),
        ':bco'      => clean($_POST['billing_country']    ?? ''),
        ':bst'      => clean($_POST['billing_state']      ?? ''),
        ':bci'      => clean($_POST['billing_city']       ?? ''),
        ':bzi'      => clean($_POST['billing_zip']        ?? ''),
    ]);
}

$_SESSION['payment_order_id'] = $order['id'];
$_SESSION['plan']             = $plan;

// ── Response ──────────────────────────────────────────────
jsonResponse(true, 'Order created.', [
    'order_id' => $order['id'],
    'amount'   => $totalAmount,
    'currency' => 'INR',
    'key'      => RAZORPAY_KEY_ID,
    'name'     => FROM_NAME,
    'reference'=> $_SESSION['application_ref'] ?? '',
]);
