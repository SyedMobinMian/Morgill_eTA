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

// Monthly summary (last 6 months): forms filled + payments received.
$monthKeys = [];
$monthLabels = [];
$cursor = new DateTime('first day of this month');
for ($i = 0; $i < 6; $i++) {
    $monthKeys[] = $cursor->format('Y-m');
    $monthLabels[$cursor->format('Y-m')] = $cursor->format('M Y');
    $cursor->modify('-1 month');
}
$monthKeys = array_reverse($monthKeys);

$formsByMonth = array_fill_keys($monthKeys, 0);
$paymentsByMonth = array_fill_keys($monthKeys, 0.0);

$formsRows = $db->query("SELECT DATE_FORMAT(updated_at, '%Y-%m') AS ym, COUNT(*) AS cnt
    FROM travellers
    WHERE decl_accurate = 1 AND decl_terms = 1
    GROUP BY ym")->fetchAll();
foreach ($formsRows as $row) {
    $ym = (string)$row['ym'];
    if (isset($formsByMonth[$ym])) {
        $formsByMonth[$ym] = (int)$row['cnt'];
    }
}

$paymentRows = $db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, IFNULL(SUM(amount),0) AS total
    FROM payments
    WHERE status = 'captured'
    GROUP BY ym")->fetchAll();
foreach ($paymentRows as $row) {
    $ym = (string)$row['ym'];
    if (isset($paymentsByMonth[$ym])) {
        $paymentsByMonth[$ym] = (float)$row['total'];
    }
}

// Applications per month (people applied)
$appsByMonth = array_fill_keys($monthKeys, 0);
$appRows = $db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
    FROM applications
    GROUP BY ym")->fetchAll();
foreach ($appRows as $row) {
    $ym = (string)$row['ym'];
    if (isset($appsByMonth[$ym])) {
        $appsByMonth[$ym] = (int)$row['cnt'];
    }
}

$snapshotLabels = array_map(static fn($g) => $g['label'], $graph);
$snapshotValues = array_map(static fn($g) => (int)$g['value'], $graph);
$monthLabelList = array_map(static fn($ym) => $monthLabels[$ym] ?? $ym, $monthKeys);
$paymentSeries = array_map(static fn($ym) => (float)$paymentsByMonth[$ym], $monthKeys);
$appsSeries = array_map(static fn($ym) => (int)$appsByMonth[$ym], $monthKeys);

// Recent documents table ke liye last 10 records lao.
$recentDocs = $db->query("SELECT reference, payment_id, amount, currency, receipt_file, form_pdf_file, created_at
    FROM payment_documents
    ORDER BY id DESC
    LIMIT 10")->fetchAll();

renderAdminLayoutStart('Dashboard', 'dashboard');
?>

<!-- CARDS -->
<div class="dashboard-cards">
    <?php foreach ($cards as $idx => $card): ?>
        <article class="metric-card metric-card-<?= ($idx % 6) + 1 ?>">
            <h3><?= esc($card['label']) ?></h3>
            <p><?= (int)$card['value'] ?></p>
        </article>
    <?php endforeach; ?>
</div>
<!-- Business Kpi's -->
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
<!-- charts / bars -->
<section class="chart-grid">
    <section class="panel">
        <div class="graph-header">
            <h3>Performance Snapshot</h3>
            <span>Bar view of key totals</span>
        </div>
        <div class="chart-wrap">
            <canvas id="snapshotBar"></canvas>
        </div>
    </section>
    <section class="panel">
        <div class="graph-header">
            <h3>Monthly Trend</h3>
            <span>Payments received vs people applied</span>
        </div>
        <div class="chart-wrap">
            <canvas id="monthlyLine"></canvas>
        </div>
    </section>
</section>


<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const snapshotLabels = <?= json_encode($snapshotLabels, JSON_UNESCAPED_UNICODE) ?>;
const snapshotValues = <?= json_encode($snapshotValues, JSON_UNESCAPED_UNICODE) ?>;
const monthLabels = <?= json_encode($monthLabelList, JSON_UNESCAPED_UNICODE) ?>;
const paymentSeries = <?= json_encode($paymentSeries, JSON_UNESCAPED_UNICODE) ?>;
const appsSeries = <?= json_encode($appsSeries, JSON_UNESCAPED_UNICODE) ?>;

const barCtx = document.getElementById('snapshotBar');
if (barCtx) {
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: snapshotLabels,
            datasets: [{
                label: 'Count',
                data: snapshotValues,
                backgroundColor: '#0f62fe',
                borderRadius: 8,
                maxBarThickness: 36
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                },
                y: { grid: { display: false } }
            }
        }
    });
}

const lineCtx = document.getElementById('monthlyLine');
if (lineCtx) {
    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [
                {
                    label: 'Payments Received (INR)',
                    data: paymentSeries,
                    borderColor: '#169c5b',
                    backgroundColor: 'rgba(22,156,91,0.12)',
                    tension: 0.35,
                    fill: true
                },
                {
                    label: 'People Applied',
                    data: appsSeries,
                    borderColor: '#0f62fe',
                    backgroundColor: 'rgba(15,98,254,0.12)',
                    tension: 0.35,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}
</script>

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
