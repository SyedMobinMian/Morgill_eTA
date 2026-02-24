<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

requireAdmin();

if (!canManageRecords()) {
    flash('error', 'Only MasterAdmin can download documents.');
    redirectTo(baseUrl('documents.php'));
}

$type = strtolower((string)($_GET['type'] ?? ''));
$id = (int)($_GET['id'] ?? 0);

if (!in_array($type, ['receipt', 'form'], true) || $id <= 0) {
    flash('error', 'Invalid download request.');
    redirectTo(baseUrl('documents.php'));
}

$db = adminDB();
$stmt = $db->prepare("SELECT receipt_file, form_pdf_file FROM payment_documents WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();

if (!$row) {
    flash('error', 'Document not found.');
    redirectTo(baseUrl('documents.php'));
}

$relative = $type === 'receipt' ? (string)$row['receipt_file'] : (string)$row['form_pdf_file'];
if ($relative === '') {
    flash('error', 'File path missing.');
    redirectTo(baseUrl('documents.php'));
}

$base = realpath(dirname(__DIR__));
$fullPath = realpath($base . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative));

if ($fullPath === false || $base === false || strpos($fullPath, $base . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR) !== 0) {
    flash('error', 'Invalid file path.');
    redirectTo(baseUrl('documents.php'));
}

if (!is_file($fullPath)) {
    flash('error', 'File not found on disk.');
    redirectTo(baseUrl('documents.php'));
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
exit;
