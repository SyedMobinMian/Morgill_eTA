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

// Recent Email Logs filters.
$logStatus = sanitizeText($_GET['log_status'] ?? '', 10);
$logEmail = sanitizeEmail($_GET['log_email'] ?? '');
$logFrom = sanitizeText($_GET['log_from'] ?? '', 20);
$logTo = sanitizeText($_GET['log_to'] ?? '', 20);

$where = [];
$params = [];
if (in_array($logStatus, ['sent', 'failed'], true)) {
    $where[] = 'l.send_status = :send_status';
    $params[':send_status'] = $logStatus;
}
if ($logEmail !== '') {
    $where[] = 'l.recipient_email = :recipient_email';
    $params[':recipient_email'] = $logEmail;
}
if ($logFrom !== '') {
    $where[] = 'DATE(l.created_at) >= :from_date';
    $params[':from_date'] = $logFrom;
}
if ($logTo !== '') {
    $where[] = 'DATE(l.created_at) <= :to_date';
    $params[':to_date'] = $logTo;
}

$sql = "SELECT l.created_at, l.recipient_email, l.subject_line, l.send_status
    FROM admin_email_logs l";
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY l.id DESC LIMIT 100';

$logsStmt = $db->prepare($sql);
$logsStmt->execute($params);
$logs = $logsStmt->fetchAll();

renderAdminLayoutStart('Email', 'email');
?>
<form method="post" class="panel">
    <h3>Send Form Link Email</h3>

    <label>Traveller (required for token link)</label>
    <select name="traveller_id" required>
        <option value="">Select traveller</option>
        <?php foreach ($list as $item): ?>
            <option value="<?= (int)$item['id'] ?>"><?= esc($item['label']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Custom Recipient Email (optional)</label>
    <input type="email" name="custom_email" maxlength="255" placeholder="If filled, email will be sent to this address">

    <label>Country Form</label>
    <select name="country" required>
        <option value="Canada">Canada</option>
        <option value="Vietnam">Vietnam</option>
        <option value="UK">UK</option>
    </select>

    <label>Subject</label>
    <input type="text" name="subject_line" maxlength="255" placeholder="Default subject auto-generated if left blank">

    <label>Email Body (HTML supported)</label>
    <textarea name="body_html" rows="8" placeholder="Write your email body. Secure form link and footer policy will still be included."></textarea>

    <small style="color:#667085;">
        Terms note and company footer are appended automatically to every sent email.
    </small>

    <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
    <button type="submit">Send Email</button>
</form>

<h3>Recent Email Logs</h3>
<form method="get" class="panel" style="max-width:100%; margin-bottom:12px;">
    <h3 style="margin:0;">Filters</h3>

    <label>Status</label>
    <select name="log_status">
        <option value="">All</option>
        <option value="sent" <?= $logStatus === 'sent' ? 'selected' : '' ?>>Sent</option>
        <option value="failed" <?= $logStatus === 'failed' ? 'selected' : '' ?>>Failed</option>
    </select>

    <label>Recipient Email</label>
    <input type="email" name="log_email" value="<?= esc($logEmail) ?>" placeholder="recipient@example.com">

    <label>From Date</label>
    <input type="date" name="log_from" value="<?= esc($logFrom) ?>">

    <label>To Date</label>
    <input type="date" name="log_to" value="<?= esc($logTo) ?>">

    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <button type="submit">Apply Filters</button>
        <a href="<?= esc(baseUrl('email.php')) ?>" style="padding:9px 10px; border-radius:8px; border:1px solid var(--line); text-decoration:none; color:var(--text); background:#fff;">Reset</a>
    </div>
</form>

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

