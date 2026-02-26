<?php
/**
 * ============================================================
 * backend/save_passport.php
 * Traveller ke Passport aur Citizenship ki details save karne ke liye
 * ============================================================
 */

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../forms/validate.php';

// --- Basic Checks ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request.');

// Security token check (CSRF)
if (!verifyCsrf($_POST['csrf_token'] ?? '')) jsonResponse(false, 'Security token invalid.');

// Session check: Pata karo user abhi bhi active hai ya nahi
if (empty($_SESSION['application_id'])) jsonResponse(false, 'Session expired. Please start again.');

// Traveller ki ID session se nikaalo
$travellerNum  = (int)($_POST['traveller_num'] ?? 1);
$travellerDbId = $_SESSION['traveller_ids'][$travellerNum] ?? null;

if (!$travellerDbId) {
    jsonResponse(false, 'Traveller record not found.');
}

// --- Data Preparation ---
// Passport number ko uppercase mein convert kar rahe hain taaki DB mein uniformity rahe
$data = [
    'passport_country'           => clean($_POST['t_passport_country']           ?? ''),
    'passport_number'            => strtoupper(clean($_POST['t_passport_number'] ?? '')),
    'passport_number_confirm'    => strtoupper(clean($_POST['t_passport_number_confirm'] ?? '')),
    'passport_issue_date'        => clean($_POST['t_passport_issue_date']        ?? ''),
    'passport_expiry'            => clean($_POST['t_passport_expiry']            ?? ''),
    'dual_citizen'               => clean($_POST['t_dual_citizen']               ?? '0'),
    'other_citizenship_country'  => clean($_POST['t_other_citizenship_country']  ?? ''),
    'prev_canada_app'            => clean($_POST['t_prev_canada_app']            ?? '0'),
    'uci_number'                 => clean($_POST['t_uci_number']                 ?? ''),
];

// --- Validation ---
// Passport logic (expiry date, number match, etc.) check karne ke liye
$errors = validateStepPassport($data);
if (!empty($errors)) {
    jsonResponse(false, 'Please fix the errors below.', ['errors' => $errors]);
}

// --- Database Update ---
$db = getDB();
try {
    // Prepared statement use kar rahe hain security ke liye
    $stmt = $db->prepare("UPDATE travellers SET 
        passport_country=:pc, 
        passport_number=:pn, 
        passport_issue_date=:pid, 
        passport_expiry=:pe, 
        dual_citizen=:dc, 
        other_citizenship_country=:occ, 
        prev_canada_app=:pca, 
        uci_number=:uci 
        WHERE id=:id");

    $stmt->execute([
        ':pc'  => $data['passport_country'],
        ':pn'  => $data['passport_number'],
        ':pid' => $data['passport_issue_date'],
        ':pe'  => $data['passport_expiry'],
        ':dc'  => $data['dual_citizen'],
        ':occ' => $data['other_citizenship_country'],
        ':pca' => $data['prev_canada_app'],
        ':uci' => $data['uci_number'],
        ':id'  => $travellerDbId,
    ]);

    // Sab set hai, frontend ko success signal bhej do
    jsonResponse(true, 'Passport details saved.');

} catch (PDOException $e) {
    // Agar DB mein duplicate passport ya koi aur error aaye
    error_log("Passport Save Error: " . $e->getMessage());
    jsonResponse(false, 'Could not save passport details. Please check if data is correct.');
}
