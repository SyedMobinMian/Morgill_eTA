<?php
/**
 * ============================================================
 * backend/save_background.php
 * Traveller ki Background aur Medical history save karne ke liye.
 * Note: Ye data visa processing ke liye sabse zyada important hai.
 * ============================================================
 */

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../forms/validate.php';

// --- Pehle wahi basic security checks ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request.');

// CSRF check: Taaki koi random bot hamare endpoint ko hit na kare
if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    jsonResponse(false, 'Security token invalid.');
}

// Session expired check
if (empty($_SESSION['application_id'])) {
    jsonResponse(false, 'Session expired. Please start again.');
}

// Traveller ki ID dhoondo session mein
$travellerNum  = (int)($_POST['traveller_num'] ?? 1);
$travellerDbId = $_SESSION['traveller_ids'][$travellerNum] ?? null;

if (!$travellerDbId) {
    jsonResponse(false, 'Traveller record not found.');
}

// --- Data Collect Karo ---
// Background questions: Inme aksar 'Yes/No' aur uske details hote hain
$data = [
    'visa_refusal'         => clean($_POST['t_visa_refusal']         ?? ''),
    'visa_refusal_details' => clean($_POST['t_visa_refusal_details'] ?? ''),
    'tuberculosis'         => clean($_POST['t_tuberculosis']         ?? ''),
    'tuberculosis_details' => clean($_POST['t_tuberculosis_details'] ?? ''),
    'criminal_history'     => clean($_POST['t_criminal_history']     ?? ''),
    'criminal_details'     => clean($_POST['t_criminal_details']     ?? ''),
    'health_condition'     => clean($_POST['t_health_condition']     ?? ''),
];

// --- Validation ---
// Yahan check hoga ki agar 'Yes' bola hai toh details fill ki hain ya nahi
$errors = validateStepBackground($data);
if (!empty($errors)) {
    jsonResponse(false, 'Please fix the errors below.', ['errors' => $errors]);
}

// --- Database Update ---
$db = getDB();

try {
    // Background info ko DB mein update kar rahe hain
    $stmt = $db->prepare("UPDATE travellers SET 
        visa_refusal=:vr, 
        visa_refusal_details=:vrd, 
        tuberculosis=:tb, 
        tuberculosis_details=:tbd, 
        criminal_history=:ch, 
        criminal_details=:chd, 
        health_condition=:hc 
        WHERE id=:id");

    $stmt->execute([
        ':vr'  => $data['visa_refusal'],
        ':vrd' => $data['visa_refusal_details'],
        ':tb'  => $data['tuberculosis'],
        ':tbd' => $data['tuberculosis_details'],
        ':ch'  => $data['criminal_history'],
        ':chd' => $data['criminal_details'],
        ':hc'  => $data['health_condition'],
        ':id'  => $travellerDbId,
    ]);

    // Sab save ho gaya!
    jsonResponse(true, 'Background details saved.');

} catch (PDOException $e) {
    // Log error aur user ko user-friendly message dikhao
    error_log("Background Save Error: " . $e->getMessage());
    jsonResponse(false, 'A database error occurred while saving background info.');
}
