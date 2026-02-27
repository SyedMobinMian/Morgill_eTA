<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

$token = trim((string)($_GET['token'] ?? ''));
if ($token === '' || !preg_match('/^[a-f0-9]{48}$/', $token)) {
    http_response_code(400);
    echo 'Invalid access token.';
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT fat.form_number, fat.form_country, t.first_name, t.last_name, t.email, a.reference, t.step_completed
    FROM form_access_tokens fat
    INNER JOIN travellers t ON t.id = fat.traveller_id
    INNER JOIN applications a ON a.id = t.application_id
    WHERE fat.token = :token
    LIMIT 1");
$stmt->execute([':token' => $token]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo 'Form link expired or not found.';
    exit;
}

$country = (string)$row['form_country'];
$formPathMap = [
    'Canada' => 'form.php?country=Canada',
    'Vietnam' => 'form.php?country=Vietnam',
    'UK' => 'form.php?country=UK',
];
$formPath = $formPathMap[$country] ?? ('form.php?country=' . urlencode($country));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Form Access</title>
    <style>
        body { font-family: Segoe UI, Tahoma, sans-serif; margin: 0; background: #f4f7fa; color: #11223a; }
        .wrap { max-width: 720px; margin: 40px auto; background: #fff; border: 1px solid #d8e0ec; border-radius: 12px; padding: 22px; }
        h1 { margin: 0 0 10px; }
        p { margin: 8px 0; }
        .num { font-weight: 700; color: #0f62fe; }
        a.btn { display: inline-block; margin-top: 14px; background: #0f62fe; color: #fff; text-decoration: none; padding: 10px 14px; border-radius: 8px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1><?= htmlspecialchars($row['form_country'], ENT_QUOTES, 'UTF-8') ?> Form</h1>
    <p>Hello <?= htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name']), ENT_QUOTES, 'UTF-8') ?>,</p>
    <p>Your unique form number: <span class="num"><?= htmlspecialchars($row['form_number'], ENT_QUOTES, 'UTF-8') ?></span></p>
    <p>Application reference: <?= htmlspecialchars($row['reference'], ENT_QUOTES, 'UTF-8') ?></p>
    <p>Email: <?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></p>
    <p>Current form step: <?= htmlspecialchars($row['step_completed'] ?: 'Not Started', ENT_QUOTES, 'UTF-8') ?></p>
    <a class="btn" href="<?= htmlspecialchars(rtrim(APP_URL, '/') . '/' . $formPath, ENT_QUOTES, 'UTF-8') ?>">Open Main Form</a>
</div>
</body>
</html>



