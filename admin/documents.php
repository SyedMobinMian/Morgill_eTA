<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requireAdmin();

$db = adminDB();
$canManage = canManageRecords();
$applicationId = (int)($_GET['application_id'] ?? 0);

// Documents page ke liye payment docs + traveler/app info join kiya ja raha hai.
$sql = "SELECT
    pd.id,
    pd.application_id,
    pd.reference,
    pd.payment_id,
    pd.amount,
    pd.currency,
    pd.receipt_file,
    pd.form_pdf_file,
    pd.created_at,
    a.status AS application_status,
    CONCAT(TRIM(t.first_name), ' ', TRIM(t.last_name)) AS traveler_name,
    t.email
FROM payment_documents pd
INNER JOIN applications a ON a.id = pd.application_id
LEFT JOIN travellers t ON t.application_id = a.id AND t.traveller_number = 1
";
$params = [];
if ($applicationId > 0) {
    $sql .= " WHERE pd.application_id = :application_id";
    $params[':application_id'] = $applicationId;
}
$sql .= " ORDER BY pd.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

renderAdminLayoutStart('Documents', 'documents');
?>
<h3 style="margin:0 0 12px;">Payment Receipts & Form PDFs</h3>
<?php if ($applicationId > 0): ?>
    <p style="margin:0 0 12px;color:var(--muted);">Showing documents for Application ID: <?= (int)$applicationId ?></p>
<?php endif; ?>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Reference</th>
            <th>Traveler</th>
            <th>Email</th>
            <th>Payment ID</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Receipt</th>
            <th>Form PDF</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
            <tr>
                <td colspan="9" style="text-align:center;color:var(--muted);">
                    No documents found<?= $applicationId > 0 ? ' for this application.' : '.' ?>
                </td>
            </tr>
        <?php endif; ?>
        <!-- Har document row me download URLs dynamic ban rahe hain -->
        <?php foreach ($rows as $row): ?>
            <?php
                // Relative file path ko full public URL me convert karo.
                $receiptUrl = rtrim(APP_URL, '/') . '/admin/download.php?type=receipt&id=' . (int)$row['id'];
                $formUrl = rtrim(APP_URL, '/') . '/admin/download.php?type=form&id=' . (int)$row['id'];
            ?>
            <tr>
                <td><?= esc($row['created_at']) ?></td>
                <td><?= esc($row['reference']) ?></td>
                <td><?= esc($row['traveler_name'] ?: '-') ?></td>
                <td><?= esc($row['email'] ?: '-') ?></td>
                <td><?= esc($row['payment_id']) ?></td>
                <td><?= esc(number_format((float)$row['amount'], 2) . ' ' . $row['currency']) ?></td>
                <td><?= esc(ucfirst((string)$row['application_status'])) ?></td>
                <td>
                    <?php if ($canManage): ?>
                        <a href="<?= esc($receiptUrl) ?>" target="_blank" rel="noopener">Download</a>
                    <?php else: ?>
                        <span style="color:var(--muted);">View only</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($canManage): ?>
                        <a href="<?= esc($formUrl) ?>" target="_blank" rel="noopener">Download</a>
                    <?php else: ?>
                        <span style="color:var(--muted);">View only</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php renderAdminLayoutEnd(); ?>
