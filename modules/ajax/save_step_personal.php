<?php
header('Content-Type: application/json');

session_start();
require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../forms/validate.php';

// ── Method check ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

// ── CSRF ──────────────────────────────────────────────────
if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    jsonResponse(false, 'Security token invalid. Please refresh the page.');
}

// ── Session check ─────────────────────────────────────────
if (empty($_SESSION['application_id'])) {
    jsonResponse(false, 'Session expired. Please start from Step 1.');
}

// ── Traveller check ───────────────────────────────────────
$travellerNum  = (int)($_POST['traveller_num'] ?? 1);
$travellerDbId = $_SESSION['traveller_ids'][$travellerNum] ?? null;

if (!$travellerDbId) {
    jsonResponse(false, 'Traveller not found. Please complete Contact Details first.');
}

// ── Collect data ──────────────────────────────────────────
$data = [
    'date_of_birth'    => trim($_POST['t_date_of_birth']    ?? ''),
    'gender'           => trim($_POST['t_gender']           ?? ''),
    'country_of_birth' => trim($_POST['t_country_of_birth'] ?? ''),
    'city_of_birth'    => trim($_POST['t_city_of_birth']    ?? ''),
    'marital_status'   => trim($_POST['t_marital_status']   ?? ''),
    'nationality'      => trim($_POST['t_nationality']      ?? ''),
];

// Clean each value
foreach ($data as $k => $v) {
    $data[$k] = clean($v);
}
// DOB: keep null if empty
if ($data['date_of_birth'] === '') $data['date_of_birth'] = null;

// ── Validate ──────────────────────────────────────────────
$errors = [];

// Date of birth
if (empty($data['date_of_birth'])) {
    $errors['date_of_birth'] = 'Date of Birth is required.';
} elseif ($err = validatePastDate($data['date_of_birth'], 'Date of Birth')) {
    $errors['date_of_birth'] = $err;
}

// Gender
if (empty($data['gender'])) {
    $errors['gender'] = 'Please select gender.';
}

// Country of birth
if (empty($data['country_of_birth'])) {
    $errors['country_of_birth'] = 'Country of Birth is required.';
}

// City of birth
if (empty($data['city_of_birth'])) {
    $errors['city_of_birth'] = 'City / Town of Birth is required.';
}

// Marital status
if (empty($data['marital_status'])) {
    $errors['marital_status'] = 'Please select Marital Status.';
}

// Nationality
if (empty($data['nationality'])) {
    $errors['nationality'] = 'Nationality is required.';
}

if (!empty($errors)) {
    jsonResponse(false, 'Please fix the errors below.', ['errors' => $errors]);
}

// ── Save to DB ────────────────────────────────────────────
$db = getDB();
$stmt = $db->prepare("
    UPDATE travellers SET
        date_of_birth    = :dob,
        gender           = :gender,
        country_of_birth = :cob,
        city_of_birth    = :cityb,
        marital_status   = :ms,
        nationality      = :nat,
        step_completed   = 'personal'
    WHERE id = :id
");
$stmt->execute([
    ':dob'    => $data['date_of_birth'],
    ':gender' => $data['gender'],
    ':cob'    => $data['country_of_birth'],
    ':cityb'  => $data['city_of_birth'],
    ':ms'     => $data['marital_status'],
    ':nat'    => $data['nationality'],
    ':id'     => $travellerDbId,
]);

jsonResponse(true, 'Personal details saved.');

