<?php
/**
 * ============================================================
 * backend/save_residential.php
 * Traveller ki address aur job details update karne ke liye
 * ============================================================
 */

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../validate.php';

// --- Basic Security Checks ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request.');

// CSRF token check kar rahe hain taaki koi bahar se script na chala sake
if (!verifyCsrf($_POST['csrf_token'] ?? '')) jsonResponse(false, 'Security token invalid.');

// Session check: User login hai ya process beech mein toh nahi choda?
if (empty($_SESSION['application_id'])) jsonResponse(false, 'Session expired. Please start again.');

// Pata karo kaunse number ka traveller update ho raha hai (1st, 2nd etc.)
$travellerNum  = (int)($_POST['traveller_num'] ?? 1);
$travellerDbId = $_SESSION['traveller_ids'][$travellerNum] ?? null;

if (!$travellerDbId) jsonResponse(false, 'Traveller record not found.');

// --- Data Collection & Cleaning ---
// Saara POST data array mein daal rahe hain clean karke
$data = [
    'address_line'     => clean($_POST['t_address_line']     ?? ''),
    'street_number'    => clean($_POST['t_street_number']    ?? ''),
    'apartment_number' => clean($_POST['t_apartment_number'] ?? ''),
    'country'          => clean($_POST['t_country']          ?? ''),
    'city'             => clean($_POST['t_city']             ?? ''),
    'postal_code'      => clean($_POST['t_postal_code']      ?? ''),
    'state'            => clean($_POST['t_state']            ?? ''),
    'occupation'       => clean($_POST['t_occupation']       ?? ''),
    'job_title'        => clean($_POST['t_job_title']        ?? ''),
    'employer_name'    => clean($_POST['t_employer_name']    ?? ''),
    'employer_country' => clean($_POST['t_employer_country'] ?? ''),
    'employer_city'    => clean($_POST['t_employer_city']    ?? ''),
    'start_year'       => clean($_POST['t_start_year']       ?? ''),
];

// --- Validation ---
// 'validate.php' se rules check karo
$errors = validateStepResidential($data);
if (!empty($errors)) {
    jsonResponse(false, 'Please fix the errors below.', ['errors' => $errors]);
}

// --- Logic: Job hai ya nahi? ---
// Agar user Retired ya Unemployed hai, toh 'has_job' ko 0 rakhenge 
// taaki frontend pe faltu fields na dikhane padein
$noJob = ['Retired', 'Unemployed', 'Homemaker'];
$hasJob = !empty($data['occupation']) && !in_array($data['occupation'], $noJob) ? 1 : 0;

// --- Database Update ---
$db = getDB();
try {
    $stmt = $db->prepare("UPDATE travellers SET 
        address_line=:al, street_number=:sn, apartment_number=:an, 
        country=:co, city=:ci, postal_code=:pc, state=:st, 
        occupation=:oc, has_job=:hj, job_title=:jt, employer_name=:en, 
        employer_country=:ec, employer_city=:ecy, start_year=:sy 
        WHERE id=:id");

    $stmt->execute([
        ':al'  => $data['address_line'],
        ':sn'  => $data['street_number'],
        ':an'  => $data['apartment_number'],
        ':co'  => $data['country'],
        ':ci'  => $data['city'],
        ':pc'  => $data['postal_code'],
        ':st'  => $data['state'],
        ':oc'  => $data['occupation'],
        ':hj'  => $hasJob,
        ':jt'  => $data['job_title'],
        ':en'  => $data['employer_name'],
        ':ec'  => $data['employer_country'],
        ':ecy' => $data['employer_city'],
        ':sy'  => $data['start_year'],
        ':id'  => $travellerDbId,
    ]);

    jsonResponse(true, 'Residential details saved.');

} catch (PDOException $e) {
    // Database level error handle karne ke liye
    error_log("DB Update Error: " . $e->getMessage());
    jsonResponse(false, 'Database error occurred while saving.');
}