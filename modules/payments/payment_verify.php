<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../forms/send_email.php';
require_once __DIR__ . '/documents.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    jsonResponse(false, 'Security token invalid. Please refresh and try again.');
}

if (empty($_SESSION['application_id'])) {
    jsonResponse(false, 'Session expired. Please start again.');
}

$applicationId = (int) $_SESSION['application_id'];
$orderId = clean($_POST['order_id'] ?? '');
$cardId = clean($_POST['card_id'] ?? '');

if ($orderId === '' || $cardId === '') {
    jsonResponse(false, 'Incomplete payment data received.');
}

if (!defined('MW_CLIENT_ID') || MW_CLIENT_ID === '' || !defined('MW_CLIENT_SECRET') || MW_CLIENT_SECRET === '' || !defined('MW_MMID') || MW_MMID === '') {
    jsonResponse(false, 'Payment gateway is not configured. Please contact support.');
}

$db = getDB();
ensurePaymentDocumentTable($db);

$paymentStmt = $db->prepare(
    'SELECT id, amount, currency, plan, billing_first_name, billing_last_name, billing_email, billing_country
     FROM payments
     WHERE application_id = :app_id AND razorpay_order_id = :oid
     ORDER BY id DESC LIMIT 1'
);
$paymentStmt->execute([
    ':app_id' => $applicationId,
    ':oid' => $orderId,
]);
$paymentRow = $paymentStmt->fetch(PDO::FETCH_ASSOC);

if (!$paymentRow) {
    jsonResponse(false, 'Payment session not found. Please retry payment.');
}

$amount = number_format((float)$paymentRow['amount'], 2, '.', '');
$currency = strtoupper((string)($paymentRow['currency'] ?: 'USD'));
$transactionProduct = 'eTA Application Fee';
$customerName = trim((string)($paymentRow['billing_first_name'] . ' ' . $paymentRow['billing_last_name']));
$customerEmail = (string)($paymentRow['billing_email'] ?? '');
$customerCountry = (string)($paymentRow['billing_country'] ?? '');
$customerIP = (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
$customerCountryCode = strtoupper(trim($customerCountry));
if (!preg_match('/^[A-Z]{2}$/', $customerCountryCode)) {
    $customerCountryCode = '';
}

$hash = md5(strtolower(MW_MMID . MW_CLIENT_ID . $amount . $currency));

$postData = [
    'merchantUUID' => MW_CLIENT_ID,
    'apiKey' => MW_CLIENT_SECRET,
    'transactionAmount' => $amount,
    'transactionCurrency' => $currency,
    'transactionProduct' => $transactionProduct,
    'customerName' => $customerName,
    'customerEmail' => $customerEmail,
    'customerIP' => $customerIP,
    'cardID' => $cardId,
    'hash' => $hash,
];
if ($customerCountryCode !== '') {
    $postData['customerCountry'] = $customerCountryCode;
}

$apiBase = trim((string)MW_API_BASE);
$parts = parse_url($apiBase);
$scheme = $parts['scheme'] ?? 'https';
$host = $parts['host'] ?? 'base.merchantwarrior.com';
$port = isset($parts['port']) ? ':' . (int)$parts['port'] : '';
$gatewayUrl = $scheme . '://' . $host . $port . '/token/processCard';
$ch = curl_init($gatewayUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($postData),
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 45,
]);
$rawResponse = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    error_log('Merchant Warrior CURL error: ' . $curlErr);
    jsonResponse(false, 'Could not connect to payment gateway. Please try again.');
}

if ($httpCode !== 200 || !$rawResponse) {
    error_log('Merchant Warrior HTTP error: ' . $httpCode . ' | ' . $rawResponse);
    jsonResponse(false, 'Payment gateway error. Please try again later.');
}

$xml = @simplexml_load_string($rawResponse);
if ($xml === false) {
    error_log('Merchant Warrior XML parse error: ' . $rawResponse);
    jsonResponse(false, 'Unexpected gateway response. Please contact support.');
}

$responseCode = (string)($xml->responseCode ?? '');
$responseMessage = (string)($xml->responseMessage ?? 'Unknown gateway response');

if ($responseCode !== '0') {
    error_log('Merchant Warrior payment failed: ' . $responseCode . ' | ' . $responseMessage);
    $db->prepare('UPDATE payments SET status = :status WHERE id = :id')
        ->execute([':status' => 'failed', ':id' => (int)$paymentRow['id']]);
    jsonResponse(false, 'Payment failed: ' . $responseMessage);
}

$transactionId = (string)($xml->transactionID ?? '');
$authCode = (string)($xml->authCode ?? '');
$receiptNo = (string)($xml->receiptNo ?? '');
$customHash = (string)($xml->customHash ?? '');

if ($transactionId === '') {
    error_log('Merchant Warrior success without transactionID: ' . $rawResponse);
    jsonResponse(false, 'Payment response incomplete. Please contact support.');
}

try {
    $db->beginTransaction();

    $update = $db->prepare(
        "UPDATE payments
         SET razorpay_payment_id = :pid,
             razorpay_signature = :sig,
             status = 'captured'
         WHERE id = :id"
    );
    $update->execute([
        ':pid' => $transactionId,
        ':sig' => ($authCode !== '' ? $authCode : $customHash),
        ':id' => (int)$paymentRow['id'],
    ]);

    $db->prepare("UPDATE applications SET status = 'paid' WHERE id = :id")
        ->execute([':id' => $applicationId]);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    error_log('Payment DB update error: ' . $e->getMessage());
    jsonResponse(false, 'Payment received but database error occurred. Please contact support. Payment ID: ' . $transactionId);
}

$reference = $_SESSION['application_ref'] ?? '';
$plan = $_SESSION['plan'] ?? ($paymentRow['plan'] ?? 'standard');

$stmt2 = $db->prepare('SELECT * FROM travellers WHERE application_id = :id ORDER BY traveller_number');
$stmt2->execute([':id' => $applicationId]);
$travellers = $stmt2->fetchAll();

$docs = generatePaymentDocuments($db, $applicationId, $reference, $transactionId, (float)$amount, $currency);

$insertDoc = $db->prepare(
    'INSERT INTO payment_documents
        (application_id, payment_id, reference, receipt_file, form_pdf_file, amount, currency)
     VALUES
        (:application_id, :payment_id, :reference, :receipt_file, :form_pdf_file, :amount, :currency)'
);
$insertDoc->execute([
    ':application_id' => $applicationId,
    ':payment_id' => $transactionId,
    ':reference' => $reference,
    ':receipt_file' => $docs['receipt_rel'],
    ':form_pdf_file' => $docs['form_rel'],
    ':amount' => (float)$amount,
    ':currency' => $currency,
]);

$appData = [
    'reference' => $reference,
    'processing_plan' => $plan,
    'primary_email' => $travellers[0]['email'] ?? '',
    'primary_name' => trim(($travellers[0]['first_name'] ?? '') . ' ' . ($travellers[0]['last_name'] ?? '')),
];

ensureSystemEmailLogTable($db);
$alreadySentStmt = $db->prepare("SELECT COUNT(*) FROM system_email_logs WHERE application_id=:application_id AND email_type='payment_receipt' AND send_status='sent'");
$alreadySentStmt->execute([':application_id' => $applicationId]);
$alreadySent = (int)$alreadySentStmt->fetchColumn() > 0;

if (!$alreadySent) {
    $amountMinor = (int)round(((float)$amount) * 100);
    [$sent, $mailError] = sendPaymentConfirmationEmail($appData, $transactionId, $amountMinor, [
        ['path' => $docs['receipt_abs'], 'name' => 'payment-receipt-' . $reference . '.pdf'],
    ]);

    logSystemEmail(
        $db,
        $applicationId,
        $reference,
        'payment_receipt',
        (string)($appData['primary_email'] ?? ''),
        'Payment Receipt | Ref ' . $reference,
        $sent,
        $mailError,
        $docs['receipt_rel']
    );
}

session_destroy();

jsonResponse(true, 'Payment verified successfully.', [
    'reference' => $reference,
    'payment_id' => $transactionId,
    'receipt_no' => $receiptNo,
    'redirect' => 'thank-you.php?ref=' . urlencode($reference),
]);
