<?php
// ============================================================
// backend/payment_verify.php - Razorpay Signature Verify
// ============================================================
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../forms/send_email.php';
require_once __DIR__ . '/documents.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

if (empty($_SESSION['application_id'])) {
    jsonResponse(false, 'Session expired. Please start again.');
}

$applicationId = (int) $_SESSION['application_id'];
$orderId = clean($_POST['razorpay_order_id'] ?? '');
$paymentId = clean($_POST['razorpay_payment_id'] ?? '');
$signature = clean($_POST['razorpay_signature'] ?? '');

if (!$orderId || !$paymentId || !$signature) {
    jsonResponse(false, 'Incomplete payment data received.');
}

$expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);
if (!hash_equals($expectedSignature, $signature)) {
    error_log("Razorpay signature mismatch - Order: $orderId | Payment: $paymentId");
    jsonResponse(false, 'Payment verification failed. Please contact support with Payment ID: ' . $paymentId);
}

$db = getDB();
ensurePaymentDocumentTable($db);

try {
    $db->beginTransaction();

    $stmt = $db->prepare("UPDATE payments
        SET razorpay_payment_id = :pid,
            razorpay_signature = :sig,
            status = 'captured'
        WHERE application_id = :app_id
          AND razorpay_order_id = :oid");
    $stmt->execute([
        ':pid' => $paymentId,
        ':sig' => $signature,
        ':app_id' => $applicationId,
        ':oid' => $orderId,
    ]);

    $db->prepare("UPDATE applications SET status='paid' WHERE id=:id")
        ->execute([':id' => $applicationId]);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    error_log('Payment DB update error: ' . $e->getMessage());
    jsonResponse(false, 'Payment received but database error occurred. Please contact support. Payment ID: ' . $paymentId);
}

$stmt = $db->prepare("SELECT amount, currency FROM payments WHERE application_id=:id AND status='captured' ORDER BY id DESC LIMIT 1");
$stmt->execute([':id' => $applicationId]);
$pmt = (array)$stmt->fetch();
$amount = (float)($pmt['amount'] ?? 0);
$currency = $pmt['currency'] ?? 'INR';
$amountPaise = (int)round($amount * 100);

$reference = $_SESSION['application_ref'] ?? '';
$plan = $_SESSION['plan'] ?? 'standard';

$stmt2 = $db->prepare("SELECT * FROM travellers WHERE application_id=:id ORDER BY traveller_number");
$stmt2->execute([':id' => $applicationId]);
$travellers = $stmt2->fetchAll();

$docs = generatePaymentDocuments($db, $applicationId, $reference, $paymentId, $amount, $currency);

$insertDoc = $db->prepare("INSERT INTO payment_documents
    (application_id, payment_id, reference, receipt_file, form_pdf_file, amount, currency)
    VALUES (:application_id, :payment_id, :reference, :receipt_file, :form_pdf_file, :amount, :currency)");
$insertDoc->execute([
    ':application_id' => $applicationId,
    ':payment_id' => $paymentId,
    ':reference' => $reference,
    ':receipt_file' => $docs['receipt_rel'],
    ':form_pdf_file' => $docs['form_rel'],
    ':amount' => $amount,
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
    [$sent, $mailError] = sendPaymentConfirmationEmail($appData, $paymentId, $amountPaise, [
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
    'payment_id' => $paymentId,
    'redirect' => 'thank-you.php?ref=' . urlencode($reference),
]);

