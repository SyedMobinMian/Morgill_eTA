<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/../core/mailer.php';
requireRole(['master', 'admin']);

$db = adminDB();

// POST par form-link email send karni hai.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (!verifyCsrf($csrf)) {
        flash('error', 'Invalid request token.');
        redirectTo(baseUrl('email.php'));
    }

    $travellerId = (int)($_POST['traveller_id'] ?? 0);
    $country = sanitizeText($_POST['country'] ?? 'Canada', 20);
    $customEmail = sanitizeEmail($_POST['custom_email'] ?? '');
    $customSubject = trim((string)($_POST['subject_line'] ?? ''));
    $customBody = trim((string)($_POST['body_html'] ?? ''));
    $allowed = ['Canada', 'Vietnam', 'UK'];

    if ($travellerId <= 0 || !in_array($country, $allowed, true)) {
        flash('error', 'Please select valid traveller and country.');
        redirectTo(baseUrl('email.php'));
    }

    $stmt = $db->prepare('SELECT id, first_name, last_name, email FROM travellers WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $travellerId]);
    $traveller = $stmt->fetch();
    if (!$traveller) {
        flash('error', 'Traveller not found.');
        redirectTo(baseUrl('email.php'));
    }

    // Default recipient traveller email hai; optional custom email override karega.
    $email = sanitizeEmail((string)$traveller['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Traveller email is invalid.');
        redirectTo(baseUrl('email.php'));
    }
    if ($customEmail !== '') {
        if (!filter_var($customEmail, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Custom recipient email is invalid.');
            redirectTo(baseUrl('email.php'));
        }
        $email = $customEmail;
    }

    $access = getOrCreateFormAccess($db, $travellerId, $country);
    $link = rtrim(APP_URL, '/') . '/form-access.php?token=' . urlencode($access['token']);

    $name = trim((string)$traveller['first_name'] . ' ' . (string)$traveller['last_name']);
    $subject = $customSubject !== '' ? $customSubject : ($country . ' Form Access - ' . $access['form_number']);

    $defaultBody = "Hello " . esc($name) . ",<br><br>" .
        "Your application form is ready.<br>" .
        "Form Number: <strong>" . esc($access['form_number']) . "</strong><br>" .
        "Country Form: <strong>" . esc($country) . "</strong><br><br>" .
        "Open your secure form link (no login required):<br>" .
        "<a href=\"" . esc($link) . "\">" . esc($link) . "</a><br><br>" .
        "Please review details carefully before submission.";

    $body = $customBody !== '' ? $customBody : $defaultBody;
    $termsFooter = "<hr>" .
        "<p style=\"font-size:12px;color:#667085;line-height:1.6;margin:0 0 8px;\">" .
        "Terms: By using this form, you confirm all submitted details are accurate and authorised by the applicant." .
        "</p>" .
        "<p style=\"font-size:12px;color:#667085;line-height:1.6;margin:0;\">" .
        "Company: " . esc(FROM_NAME) . " | Email: " . esc(ADMIN_EMAIL) .
        "</p>";
    $body = "<div style=\"font-family:Segoe UI,Arial,sans-serif;font-size:14px;color:#0f172a;line-height:1.6;\">" . $body . $termsFooter . "</div>";

    [$sent, $mailError] = sendSmtpMail($email, $name, $subject, $body, ADMIN_EMAIL, FROM_NAME);

    if ($sent) {
        $db->prepare('UPDATE form_access_tokens SET email_sent_at = NOW() WHERE id = :id')->execute([':id' => $access['id']]);
    }

    $db->prepare('INSERT INTO admin_email_logs (traveller_id, recipient_email, subject_line, send_status, error_message) VALUES (:traveller_id, :recipient_email, :subject_line, :send_status, :error_message)')
        ->execute([
            ':traveller_id' => $travellerId,
            ':recipient_email' => $email,
            ':subject_line' => $subject,
            ':send_status' => $sent ? 'sent' : 'failed',
            ':error_message' => $sent ? null : ($mailError ?: 'SMTP send failed'),
        ]);

    if ($sent) {
        flash('success', 'Email sent. Form link: ' . $link);
    } else {
        flash('error', 'Email failed: ' . ($mailError ?: 'SMTP configuration issue.'));
    }
    redirectTo(baseUrl('email.php'));
}

$list = $db->query("SELECT t.id, CONCAT(TRIM(t.first_name), ' ', TRIM(t.last_name), ' (', t.email, ')') AS label
    FROM travellers t
    WHERE t.email IS NOT NULL AND t.email <> ''
    ORDER BY t.created_at DESC
    LIMIT 200")->fetchAll();

// Recent Email Logs (no filters).
$logs = $db->query("SELECT l.created_at, l.recipient_email, l.subject_line, l.send_status
    FROM admin_email_logs l
    ORDER BY l.id DESC
    LIMIT 100")->fetchAll();

renderAdminLayoutStart('Email', 'email');
?>

<h3>Recent Email Logs</h3>

<table>
    <thead><tr><th>Date</th><th>Email</th><th>Subject</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= esc($log['created_at']) ?></td>
            <td><?= esc($log['recipient_email']) ?></td>
            <td><?= esc($log['subject_line']) ?></td>
            <td><?= esc(strtoupper($log['send_status'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php renderAdminLayoutEnd(); ?>

