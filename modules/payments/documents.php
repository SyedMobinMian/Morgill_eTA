<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

function ensurePaymentDocumentTable(PDO $db): void {
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
}

function buildAbsolutePath(string $relativePath): string {
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\\\'], DIRECTORY_SEPARATOR, $relativePath);
}

function ensureDir(string $relativeDir): void {
    $abs = buildAbsolutePath($relativeDir);
    if (!is_dir($abs)) {
        mkdir($abs, 0775, true);
    }
}

function pdfEscape(string $text): string {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function writeSimplePdf(string $absolutePath, string $title, array $lines): void {
    $objects = [];

    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
    $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

    $y = 800;
    $content = "BT\n/F1 16 Tf\n50 {$y} Td\n(" . pdfEscape($title) . ") Tj\n";
    $y -= 30;
    $content .= "/F1 10 Tf\n";

    foreach ($lines as $line) {
        if ($y < 40) {
            break;
        }
        $content .= "50 {$y} Td\n(" . pdfEscape((string)$line) . ") Tj\n";
        $y -= 16;
    }
    $content .= "ET";

    $stream = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream\nendobj\n";
    $objects[] = $stream;

    $pdf = "%PDF-1.4\n";
    $offsets = [0];

    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj;
    }

    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }

    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

    file_put_contents($absolutePath, $pdf);
}

function generatePaymentDocuments(PDO $db, int $applicationId, string $reference, string $paymentId, float $amount, string $currency = 'INR'): array {
    ensureDir('storage/receipts');
    ensureDir('storage/forms');

    $travellersStmt = $db->prepare("SELECT * FROM travellers WHERE application_id = :id ORDER BY traveller_number");
    $travellersStmt->execute([':id' => $applicationId]);
    $travellers = $travellersStmt->fetchAll();

    $appStmt = $db->prepare("SELECT * FROM applications WHERE id = :id LIMIT 1");
    $appStmt->execute([':id' => $applicationId]);
    $application = (array)$appStmt->fetch();

    $safeRef = preg_replace('/[^A-Za-z0-9\-]/', '', $reference) ?: ('APP-' . $applicationId);
    $stamp = date('YmdHis');

    $receiptRel = 'storage/receipts/receipt-' . $safeRef . '-' . $stamp . '.pdf';
    $formRel = 'storage/forms/form-' . $safeRef . '-' . $stamp . '.pdf';

    $receiptLines = [
        'Reference: ' . $reference,
        'Payment ID: ' . $paymentId,
        'Application ID: ' . $applicationId,
        'Amount: ' . number_format($amount, 2) . ' ' . $currency,
        'Travel Mode: ' . ($application['travel_mode'] ?? '-'),
        'Total Travellers: ' . ($application['total_travellers'] ?? '-'),
        'Generated At: ' . date('Y-m-d H:i:s'),
    ];
    writeSimplePdf(buildAbsolutePath($receiptRel), 'Payment Receipt', $receiptLines);

    $formLines = [
        'Reference: ' . $reference,
        'Application ID: ' . $applicationId,
        'Status: ' . ($application['status'] ?? '-'),
        'Travel Mode: ' . ($application['travel_mode'] ?? '-'),
        'Total Travellers: ' . ($application['total_travellers'] ?? '-'),
        '------------------------------------------',
    ];

    foreach ($travellers as $idx => $t) {
        $n = $idx + 1;
        $formLines[] = "Traveller {$n}: " . trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''));
        $formLines[] = 'DOB: ' . ($t['date_of_birth'] ?? '-');
        $formLines[] = 'Email: ' . ($t['email'] ?? '-');
        $formLines[] = 'Phone: ' . ($t['phone'] ?? '-');
        $formLines[] = 'Country: ' . ($t['country'] ?? '-');
        $formLines[] = 'City: ' . ($t['city'] ?? '-');
        $formLines[] = 'Passport: ' . ($t['passport_number'] ?? '-');
        $formLines[] = 'Nationality: ' . ($t['nationality'] ?? '-');
        $formLines[] = '------------------------------';
    }

    writeSimplePdf(buildAbsolutePath($formRel), 'Filled Application Form', $formLines);

    return [
        'receipt_rel' => $receiptRel,
        'form_rel' => $formRel,
        'receipt_abs' => buildAbsolutePath($receiptRel),
        'form_abs' => buildAbsolutePath($formRel),
    ];
}

function generateFormDetailsDocument(PDO $db, int $applicationId, string $reference): array {
    ensureDir('storage/forms');

    $travellersStmt = $db->prepare("SELECT * FROM travellers WHERE application_id = :id ORDER BY traveller_number");
    $travellersStmt->execute([':id' => $applicationId]);
    $travellers = $travellersStmt->fetchAll();

    $appStmt = $db->prepare("SELECT * FROM applications WHERE id = :id LIMIT 1");
    $appStmt->execute([':id' => $applicationId]);
    $application = (array)$appStmt->fetch();

    $safeRef = preg_replace('/[^A-Za-z0-9\-]/', '', $reference) ?: ('APP-' . $applicationId);
    $stamp = date('YmdHis');
    $formRel = 'storage/forms/form-submission-' . $safeRef . '-' . $stamp . '.pdf';

    $lines = [
        'Reference: ' . $reference,
        'Application ID: ' . $applicationId,
        'Status: ' . ($application['status'] ?? '-'),
        'Travel Mode: ' . ($application['travel_mode'] ?? '-'),
        'Total Travellers: ' . ($application['total_travellers'] ?? '-'),
        'Submitted At: ' . date('Y-m-d H:i:s'),
        '------------------------------------------',
    ];

    foreach ($travellers as $idx => $t) {
        $n = $idx + 1;
        $lines[] = "Traveller {$n}: " . trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''));
        $lines[] = 'DOB: ' . ($t['date_of_birth'] ?? '-');
        $lines[] = 'Email: ' . ($t['email'] ?? '-');
        $lines[] = 'Phone: ' . ($t['phone'] ?? '-');
        $lines[] = 'Passport: ' . ($t['passport_number'] ?? '-');
        $lines[] = 'Nationality: ' . ($t['nationality'] ?? '-');
        $lines[] = 'Country: ' . ($t['country'] ?? '-');
        $lines[] = 'City: ' . ($t['city'] ?? '-');
        $lines[] = '------------------------------------------';
    }

    writeSimplePdf(buildAbsolutePath($formRel), 'Application Form Details', $lines);

    return [
        'form_rel' => $formRel,
        'form_abs' => buildAbsolutePath($formRel),
    ];
}

