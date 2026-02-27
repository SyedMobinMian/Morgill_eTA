<?php
/**
 * ============================================================
 * backend/save_declaration.php
 * Final Step: User se terms accept karwana aur traveller ka status update karna.
 * ============================================================
 */

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../forms/validate.php';
require_once __DIR__ . '/../forms/send_email.php';
require_once __DIR__ . '/../payments/documents.php';

// --- Pehle wahi basic checks ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request.');

// Security ke liye CSRF token zaroori hai
if (!verifyCsrf($_POST['csrf_token'] ?? '')) jsonResponse(false, 'Security token invalid.');

// Session check karlo, kahin timeout toh nahi ho gaya
if (empty($_SESSION['application_id'])) jsonResponse(false, 'Session expired. Please start again.');

// Traveller ki ID nikaalo session se
$travellerNum  = (int)($_POST['traveller_num'] ?? 1);
$travellerDbId = $_SESSION['traveller_ids'][$travellerNum] ?? null;

if (!$travellerDbId) {
    jsonResponse(false, 'Traveller record not found.');
}

// --- Data read karo ---
// In dono ka '1' hona zaroori hai aage badhne ke liye
$data = [
    'decl_accurate' => clean($_POST['t_decl_accurate'] ?? '0'),
    'decl_terms'    => clean($_POST['t_decl_terms']    ?? '0'),
];

// Validation: Agar tick nahi kiya toh error feko
$errors = validateStepDeclaration($data);
if (!empty($errors)) {
    jsonResponse(false, 'Please accept both declarations to continue.', ['errors' => $errors]);
}

// --- DB mein status lock karo ---
$db = getDB();

// Is traveller ke saare steps poore ho gaye, isliye 'step_completed' mark kar rahe hain
$stmt = $db->prepare("
    UPDATE travellers 
    SET decl_accurate=1, 
        decl_terms=1, 
        step_completed='declaration' 
    WHERE id=:id
");
$stmt->execute([':id' => $travellerDbId]);

// --- Agla step decide karo ---
// Check kar rahe hain ki solo trip hai ya group trip
$travelMode      = $_SESSION['travel_mode']      ?? 'solo';
$totalTravellers = $_SESSION['total_travellers'] ?? 1;
$applicationId   = (int)($_SESSION['application_id'] ?? 0);
$reference       = (string)($_SESSION['application_ref'] ?? '');
$allDone         = ($travellerNum >= $totalTravellers);

/**
 * Frontend ko batayenge ki kya saare travellers ka form bhar gaya hai?
 * Agar travellerNum >= totalTravellers hai, matlab sabka data submit ho chuka hai.
 */
jsonResponse(true, 'Declaration saved.', [
    'travel_mode'      => $travelMode,
    'total_travellers' => $totalTravellers,
    'current_traveller'=> $travellerNum,
    'all_done'         => $allDone,
]);


