<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requireAdmin();

$db = adminDB();

// Dashboard cards ke counters yahan collect honge.
$stats = [
    'payment_receive' => 0,
    'payment_pending' => 0,
    'form_sent' => 0,
    'all_travellers' => 0,
    'groups' => 0,
    'solo' => 0,
    'form_filled' => 0,
    'receipts_saved' => 0,
];

// Har card ka count alag query se nikala ja raha hai.
$stats['payment_receive'] = (int)$db->query("SELECT COUNT(*) FROM applications WHERE status IN ('paid','processing','approved') OR IFNULL(amount_paid,0) > 0")->fetchColumn();
$stats['payment_pending'] = (int)$db->query("SELECT COUNT(*) FROM applications WHERE status IN ('draft','submitted') AND IFNULL(amount_paid,0) <= 0")->fetchColumn();
$stats['form_sent'] = (int)$db->query("SELECT COUNT(*) FROM form_access_tokens WHERE email_sent_at IS NOT NULL")->fetchColumn();
$stats['all_travellers'] = (int)$db->query('SELECT COUNT(*) FROM travellers')->fetchColumn();
$stats['groups'] = (int)$db->query("SELECT COUNT(*) FROM applications WHERE travel_mode = 'group'")->fetchColumn();
$stats['solo'] = (int)$db->query("SELECT COUNT(*) FROM applications WHERE travel_mode = 'solo'")->fetchColumn();
$stats['form_filled'] = (int)$db->query("SELECT COUNT(*) FROM travellers WHERE decl_accurate = 1 AND decl_terms = 1")->fetchColumn();
$stats['receipts_saved'] = (int)$db->query("SELECT COUNT(*) FROM payment_documents")->fetchColumn();

// Recent documents table ke liye last 10 records lao.
$recentDocs = $db->query("SELECT reference, payment_id, amount, currency, receipt_file, form_pdf_file, created_at
    FROM payment_documents
    ORDER BY id DESC
    LIMIT 10")->fetchAll();

renderAdminLayoutStart('Dashboard', 'dashboard');
?>
<div class="cards">
    <article><h3>Payment Receive</h3><p><?= (int)$stats['payment_receive'] ?></p></article>
    <article><h3>Payment Pending</h3><p><?= (int)$stats['payment_pending'] ?></p></article>
    <article><h3>Form Sent</h3><p><?= (int)$stats['form_sent'] ?></p></article>
    <article><h3>All Travelers</h3><p><?= (int)$stats['all_travellers'] ?></p></article>
    <article><h3>Number of Groups</h3><p><?= (int)$stats['groups'] ?></p></article>
    <article><h3>Number of Solo</h3><p><?= (int)$stats['solo'] ?></p></article>
    <article><h3>Form Filled</h3><p><?= (int)$stats['form_filled'] ?></p></article>
    <article><h3>Receipts Saved</h3><p><?= (int)$stats['receipts_saved'] ?></p></article>
</div>

<h3 style="margin-top:16px;">Recent Payment Documents</h3>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Reference</th>
            <th>Payment ID</th>
            <th>Amount</th>
            <th>Receipt File</th>
            <th>Form PDF</th>
        </tr>
    </thead>
    <tbody>
        <!-- Recent docs ko loop karke rows render ho rahi hain -->
        <?php foreach ($recentDocs as $doc): ?>
            <tr>
                <td><?= esc($doc['created_at']) ?></td>
                <td><?= esc($doc['reference']) ?></td>
                <td><?= esc($doc['payment_id']) ?></td>
                <td><?= esc(number_format((float)$doc['amount'], 2) . ' ' . $doc['currency']) ?></td>
                <td><?= esc($doc['receipt_file']) ?></td>
                <td><?= esc($doc['form_pdf_file']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php renderAdminLayoutEnd(); ?>
