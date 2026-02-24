<?php
/**
 * ============================================================
 * backend/send_email.php - Email notifications ka logic
 * ============================================================
 */

require_once __DIR__ . '/mailer.php';

function ensureSystemEmailLogTable(PDO $db): void {
    // System-generated emails ka audit trail yahan store hota hai.
    $db->exec("CREATE TABLE IF NOT EXISTS system_email_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        application_id INT UNSIGNED NOT NULL,
        reference VARCHAR(50) DEFAULT NULL,
        email_type ENUM('form_submitted','payment_receipt') NOT NULL,
        recipient_email VARCHAR(255) NOT NULL,
        subject_line VARCHAR(255) NOT NULL,
        send_status ENUM('sent','failed') NOT NULL,
        error_message VARCHAR(255) DEFAULT NULL,
        attachment_path VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_system_email_app (application_id),
        INDEX idx_system_email_type (email_type),
        CONSTRAINT fk_system_email_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function logSystemEmail(
    PDO $db,
    int $applicationId,
    string $reference,
    string $emailType,
    string $recipientEmail,
    string $subject,
    bool $sent,
    ?string $error = null,
    ?string $attachmentPath = null
): void {
    // Insert se pehle table ka wujood ensure karo.
    ensureSystemEmailLogTable($db);
    $stmt = $db->prepare("INSERT INTO system_email_logs
        (application_id, reference, email_type, recipient_email, subject_line, send_status, error_message, attachment_path)
        VALUES
        (:application_id, :reference, :email_type, :recipient_email, :subject_line, :send_status, :error_message, :attachment_path)");
    $stmt->execute([
        ':application_id' => $applicationId,
        ':reference' => $reference,
        ':email_type' => $emailType,
        ':recipient_email' => $recipientEmail,
        ':subject_line' => $subject,
        ':send_status' => $sent ? 'sent' : 'failed',
        ':error_message' => $sent ? null : ($error ?: 'SMTP send failed'),
        ':attachment_path' => $attachmentPath,
    ]);
}

function mailEsc(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Reusable responsive email shell.
 *
 * @param array<int, array{label:string,value:string}> $summaryRows
 * @param array<int, string> $nextSteps
 */
function buildResponsiveEmailHtml(
    string $preheader,
    string $headline,
    string $greetingName,
    string $introLine,
    array $summaryRows,
    array $nextSteps,
    string $accentColor,
    string $ctaLabel = '',
    string $ctaUrl = '',
    string $footNote = ''
): string {
    $brandName = FROM_NAME !== '' ? FROM_NAME : 'Application Team';
    $year = date('Y');

    $rowsHtml = '';
    foreach ($summaryRows as $row) {
        $rowsHtml .= '<tr>'
            . '<td style="padding:11px 12px;border-bottom:1px solid #e9eef5;color:#5b6878;font-size:14px;">' . mailEsc($row['label']) . '</td>'
            . '<td style="padding:11px 12px;border-bottom:1px solid #e9eef5;color:#10213a;font-size:14px;font-weight:700;text-align:right;">' . mailEsc($row['value']) . '</td>'
            . '</tr>';
    }

    $stepsHtml = '';
    foreach ($nextSteps as $step) {
        $stepsHtml .= '<tr><td style="padding:0 0 10px 0;color:#334155;font-size:14px;line-height:1.6;">'
            . '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' . mailEsc($accentColor) . ';margin-right:8px;vertical-align:middle;"></span>'
            . mailEsc($step)
            . '</td></tr>';
    }

    $ctaHtml = '';
    if ($ctaLabel !== '' && $ctaUrl !== '') {
        $ctaHtml = '<tr><td align="center" style="padding:14px 26px 10px;">'
            . '<a href="' . mailEsc($ctaUrl) . '" style="display:inline-block;background:' . mailEsc($accentColor) . ';color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:600;font-size:14px;">'
            . mailEsc($ctaLabel)
            . '</a></td></tr>';
    }

    $footNoteHtml = $footNote !== ''
        ? '<tr><td style="padding:4px 26px 0;color:#64748b;font-size:12px;line-height:1.6;">' . mailEsc($footNote) . '</td></tr>'
        : '';

    return '<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <title>' . mailEsc($headline) . '</title>
  <style>
    @media only screen and (max-width: 640px) {
      .mail-wrap { width: 100% !important; }
      .mail-pad { padding: 20px !important; }
      .mail-h1 { font-size: 23px !important; }
      .mail-stack td { display: block !important; width: 100% !important; text-align: left !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background:#eef3f8;font-family:Segoe UI,Arial,sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . mailEsc($preheader) . '</div>
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#eef3f8;padding:24px 10px;">
    <tr>
      <td align="center">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="620" class="mail-wrap" style="width:620px;max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #dde5ef;">
          <tr>
            <td style="background:' . mailEsc($accentColor) . ';padding:22px 26px;color:#ffffff;">
              <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.95;">' . mailEsc($brandName) . '</div>
              <div class="mail-h1" style="margin-top:8px;font-size:28px;line-height:1.25;font-weight:700;">' . mailEsc($headline) . '</div>
            </td>
          </tr>
          <tr>
            <td class="mail-pad" style="padding:26px;">
              <p style="margin:0 0 10px;color:#0f172a;font-size:16px;line-height:1.6;">Hello <strong>' . mailEsc($greetingName) . '</strong>,</p>
              <p style="margin:0 0 18px;color:#334155;font-size:15px;line-height:1.7;">' . mailEsc($introLine) . '</p>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e5ecf4;border-radius:12px;overflow:hidden;margin-bottom:18px;">
                <tr>
                  <td style="padding:11px 12px;background:#f8fbff;color:#0f172a;font-weight:700;font-size:14px;" colspan="2">Summary</td>
                </tr>
                ' . $rowsHtml . '
              </table>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:6px;">
                <tr>
                  <td style="padding:0 0 10px;color:#0f172a;font-size:14px;font-weight:700;">What happens next</td>
                </tr>
                ' . $stepsHtml . '
              </table>
            </td>
          </tr>
          ' . $ctaHtml . '
          ' . $footNoteHtml . '
          <tr>
            <td style="padding:18px 26px 24px;color:#7a8798;font-size:12px;line-height:1.7;border-top:1px solid #edf2f8;">
              This is an automated email from ' . mailEsc($brandName) . '.<br>
              &copy; ' . mailEsc((string)$year) . ' ' . mailEsc($brandName) . '. All rights reserved.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

/**
 * @param array<int, array{path:string,name?:string}> $attachments
 * @return array{0:bool,1:?string}
 */
function sendFormSubmittedEmail(array $application, array $travellers, array $attachments = []): array {
    // Pehle primary email lo, warna first traveller ka email fallback me lo.
    $reference = $application['reference'] ?? '';
    $toEmail = $application['primary_email'] ?? ($travellers[0]['email'] ?? '');
    // Full name banao, aur fallback values handle karo.
    $toName = trim((string)($application['primary_name'] ?? (($travellers[0]['first_name'] ?? '') . ' ' . ($travellers[0]['last_name'] ?? ''))));
    $country = $application['country'] ?? 'Canada';
    // Display ke liye minimum 1 traveller count rakho.
    $totalPax = max(1, count($travellers));

    $safeName = $toName !== '' ? $toName : 'Applicant';
    $subject = "Application Received | " . $country . " | Ref " . $reference;
    $body = buildResponsiveEmailHtml(
        'Your application has been received successfully.',
        'Application Submitted',
        $safeName,
        'We have received your ' . $country . ' application and our team has started the initial review.',
        [
            ['label' => 'Reference Number', 'value' => $reference !== '' ? $reference : 'N/A'],
            ['label' => 'Country', 'value' => $country],
            ['label' => 'Total Travellers', 'value' => (string)$totalPax],
            ['label' => 'Status', 'value' => 'Submitted'],
        ],
        [
            'Your details are being validated by our processing team.',
            'You will receive an update by email once review is complete.',
            'Keep your reference number safe for future communication.',
        ],
        '#0f62fe',
        'Open Application Portal',
        rtrim(APP_URL, '/') . '/form.php?country=' . urlencode($country),
        'Your submitted form PDF is attached for your records.'
    );

    // Agar recipient email missing ho to yahin par fail return karo.
    if ($toEmail === '') {
        return [false, 'Primary email is missing.'];
    }

    // Central SMTP function connection/auth/retry errors handle karti hai.
    return sendSmtpMail($toEmail, $toName, $subject, $body, ADMIN_EMAIL, FROM_NAME, $attachments);
}

/**
 * Payment confirmation email (optional PDF attachments ke sath).
 *
 * @param array<int, array{path:string,name?:string}> $attachments
 * @return array{0:bool,1:?string}
 */
function sendPaymentConfirmationEmail(array $application, string $paymentId, int $amountPaise, array $attachments = []): array {
    $reference = $application['reference'] ?? '';
    $plan = ucfirst($application['processing_plan'] ?? 'Standard');
    $toEmail = $application['primary_email'] ?? '';
    $toName = $application['primary_name'] ?? 'Applicant';
    // Paise ko formatted INR me convert karo.
    $amount = 'INR ' . number_format($amountPaise / 100, 2);

    $safeName = $toName !== '' ? $toName : 'Applicant';
    $subject = "Payment Receipt | Ref " . $reference . " | " . $amount;
    $body = buildResponsiveEmailHtml(
        'Your payment has been confirmed.',
        'Payment Confirmed',
        $safeName,
        'We have successfully received your payment. Your application is now in processing.',
        [
            ['label' => 'Reference Number', 'value' => $reference !== '' ? $reference : 'N/A'],
            ['label' => 'Payment ID', 'value' => $paymentId !== '' ? $paymentId : 'N/A'],
            ['label' => 'Amount Paid', 'value' => $amount],
            ['label' => 'Processing Plan', 'value' => $plan],
        ],
        [
            'Your payment receipt has been attached to this email.',
            'Our team will continue processing your application.',
            'You will get the next update on this email address.',
        ],
        '#169c5b',
        'Track Using Reference',
        rtrim(APP_URL, '/') . '/thank-you.php?ref=' . urlencode($reference),
        'If you did not perform this payment, contact support immediately.'
    );

    // Agar recipient email missing ho to yahin par fail return karo.
    if ($toEmail === '') {
        return [false, 'Primary email is missing.'];
    }

    // Central SMTP function connection/auth/retry errors handle karti hai.
    return sendSmtpMail($toEmail, $toName, $subject, $body, ADMIN_EMAIL, FROM_NAME, $attachments);
}
