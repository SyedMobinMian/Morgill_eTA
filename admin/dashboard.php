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
];

// Har card ka count alag query se nikala ja raha hai.
$stats['payment_receive'] = (int)$db->query("SELECT COUNT(*) FROM applications WHERE status IN ('paid','processing','approved') OR IFNULL(amount_paid,0) > 0")->fetchColumn();
$stats['payment_pending'] = (int)$db->query("SELECT COUNT(*) FROM applications WHERE status IN ('draft','submitted') AND IFNULL(amount_paid,0) <= 0")->fetchColumn();
$stats['form_sent'] = (int)$db->query("SELECT COUNT(*) FROM form_access_tokens WHERE email_sent_at IS NOT NULL")->fetchColumn();
$stats['all_travellers'] = (int)$db->query('SELECT COUNT(*) FROM travellers')->fetchColumn();
$stats['groups'] = (int)$db->query("SELECT COUNT(*) FROM applications WHERE travel_mode = 'group'")->fetchColumn();
$stats['solo'] = (int)$db->query("SELECT COUNT(*) FROM applications WHERE travel_mode = 'solo'")->fetchColumn();
$stats['form_filled'] = (int)$db->query("SELECT COUNT(*) FROM travellers WHERE decl_accurate = 1 AND decl_terms = 1")->fetchColumn();
$stats['all_applications'] = (int)$db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$stats['revenue_collected'] = (float)$db->query("SELECT IFNULL(SUM(amount_paid),0) FROM applications WHERE status IN ('paid','processing','approved') OR IFNULL(amount_paid,0) > 0")->fetchColumn();

$safePercent = static function (float $num, float $den): string {
    if ($den <= 0) {
        return '0%';
    }
    return number_format(($num / $den) * 100, 1) . '%';
};

$paymentConversion = $safePercent((float)$stats['payment_receive'], (float)$stats['all_applications']);
$formCompletion = $safePercent((float)$stats['form_filled'], (float)$stats['all_travellers']);
$groupShare = $safePercent((float)$stats['groups'], (float)($stats['groups'] + $stats['solo']));
$avgTicket = $stats['payment_receive'] > 0 ? ($stats['revenue_collected'] / (float)$stats['payment_receive']) : 0.0;

$kpis = [
    ['label' => 'Payment Conversion', 'value' => $paymentConversion, 'hint' => 'Paid applications / total applications'],
    ['label' => 'Form Completion', 'value' => $formCompletion, 'hint' => 'Completed forms / all travellers'],
    ['label' => 'Group Share', 'value' => $groupShare, 'hint' => 'Group applications vs total applications'],
    ['label' => 'Revenue Collected', 'value' => '$' . number_format($stats['revenue_collected'], 2), 'hint' => 'Total collected amount'],
    ['label' => 'Avg Ticket', 'value' => '$' . number_format($avgTicket, 2), 'hint' => 'Revenue / paid applications'],
];

$cards = [
    ['label' => 'Payment Received', 'value' => $stats['payment_receive']],
    ['label' => 'Payment Pending', 'value' => $stats['payment_pending']],
    ['label' => 'Form Sent', 'value' => $stats['form_sent']],
    ['label' => 'All Travelers', 'value' => $stats['all_travellers']],
    ['label' => 'Groups', 'value' => $stats['groups']],
    ['label' => 'Solo', 'value' => $stats['solo']],
    ['label' => 'Form Filled', 'value' => $stats['form_filled']],
];

$graph = [
    ['label' => 'Received', 'value' => $stats['payment_receive']],
    ['label' => 'Pending', 'value' => $stats['payment_pending']],
    ['label' => 'Forms Sent', 'value' => $stats['form_sent']],
    ['label' => 'Completed', 'value' => $stats['form_filled']],
    ['label' => 'Groups', 'value' => $stats['groups']],
    ['label' => 'Solo', 'value' => $stats['solo']],
];
$graphMax = 1;
foreach ($graph as $g) {
    if ((int)$g['value'] > $graphMax) {
        $graphMax = (int)$g['value'];
    }
}

// Recent documents table ke liye last 10 records lao.
$recentDocs = $db->query("SELECT reference, payment_id, amount, currency, receipt_file, form_pdf_file, created_at
    FROM payment_documents
    ORDER BY id DESC
    LIMIT 10")->fetchAll();

renderAdminLayoutStart('Dashboard', 'dashboard');
?>
<section class="kpi-section">
    <div class="graph-header">
        <h3>Business KPIs</h3>
        <span>Operational health snapshot</span>
    </div>
    <div class="kpi-grid">
        <?php foreach ($kpis as $kpi): ?>
            <article class="kpi-card">
                <h4><?= esc($kpi['label']) ?></h4>
                <p><?= esc($kpi['value']) ?></p>
                <small><?= esc($kpi['hint']) ?></small>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<div class="dashboard-cards">
    <?php foreach ($cards as $idx => $card): ?>
        <article class="metric-card metric-card-<?= ($idx % 6) + 1 ?>">
            <h3><?= esc($card['label']) ?></h3>
            <p><?= (int)$card['value'] ?></p>
        </article>
    <?php endforeach; ?>
</div>

<section class="dashboard-graph">
    <div class="graph-header">
        <h3>Performance Snapshot</h3>
        <span>Live totals from current records</span>
    </div>
    <div class="snapshot-list">
        <?php foreach ($graph as $g): ?>
            <?php $width = max(6, (int)round((((int)$g['value']) / $graphMax) * 100)); ?>
            <div class="snapshot-item">
                <div class="snapshot-meta">
                    <span><?= esc($g['label']) ?></span>
                    <strong><?= (int)$g['value'] ?></strong>
                </div>
                <div class="snapshot-track">
                    <div class="snapshot-fill" style="width: <?= $width ?>%"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

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
