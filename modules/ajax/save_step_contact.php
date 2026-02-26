<?php
/**
 * ============================================================
 * backend/save_contact.php
 * Form ka Pehla Step: Application create karna aur traveller ki details save karna.
 * ============================================================
 */

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../forms/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request.');

if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    jsonResponse(false, 'Security token invalid. Please refresh the page.');
}

$data = [
    'first_name'       => clean($_POST['t_first_name']       ?? ''),
    'middle_name'      => clean($_POST['t_middle_name']      ?? ''),
    'last_name'        => clean($_POST['t_last_name']        ?? ''),
    'email'            => clean($_POST['t_email']            ?? ''),
    'phone'            => clean($_POST['t_phone']            ?? ''),
    'travel_date'      => clean($_POST['t_travel_date']      ?? ''),
    'purpose_of_visit' => clean($_POST['t_purpose_of_visit'] ?? ''),
];

$errors = validateStepContact($data);
if (!empty($errors)) {
    jsonResponse(false, 'Please fix the errors below.', ['errors' => $errors]);
}

$travelMode      = in_array($_POST['travel_mode'] ?? '', ['solo','group']) ? $_POST['travel_mode'] : 'solo';
$totalTravellers = (int)($_POST['total_travellers'] ?? 1);
$travellerNum    = (int)($_POST['traveller_num'] ?? 1);

if ($totalTravellers < 1 || $totalTravellers > 10) $totalTravellers = 1;

$db = getDB();

if ($travellerNum === 1 && empty($_SESSION['application_id'])) {
    $ref  = generateReference();
    $stmt = $db->prepare("INSERT INTO applications (reference, travel_mode, total_travellers) VALUES (:ref, :mode, :total)");
    $stmt->execute([':ref' => $ref, ':mode' => $travelMode, ':total' => $totalTravellers]);

    $_SESSION['application_id']   = (int)$db->lastInsertId();
    $_SESSION['application_ref']  = $ref;
    $_SESSION['travel_mode']      = $travelMode;
    $_SESSION['total_travellers'] = $totalTravellers;
}

// Existing application ho to mode/total sync kar do (Add Another Traveller support)
if (!empty($_SESSION['application_id'])) {
    $db->prepare("UPDATE applications SET travel_mode=:mode, total_travellers=:total WHERE id=:id")
       ->execute([
            ':mode' => $travelMode,
            ':total' => $totalTravellers,
            ':id' => (int)$_SESSION['application_id'],
       ]);
    $_SESSION['travel_mode'] = $travelMode;
    $_SESSION['total_travellers'] = $totalTravellers;
}

$travellerDbId = $_SESSION['traveller_ids'][$travellerNum] ?? null;

if ($travellerDbId) {
    $stmt = $db->prepare("UPDATE travellers SET
        first_name=:fn, middle_name=:mn, last_name=:ln, email=:em,
        phone=:ph, travel_date=:td, purpose_of_visit=:pv
        WHERE id=:id");

    $stmt->execute([
        ':fn'=>$data['first_name'], ':mn'=>$data['middle_name'], ':ln'=>$data['last_name'],
        ':em'=>$data['email'], ':ph'=>$data['phone'], ':td'=>$data['travel_date'],
        ':pv'=>$data['purpose_of_visit'], ':id'=>$travellerDbId
    ]);
} else {
    $stmt = $db->prepare("INSERT INTO travellers (application_id, traveller_number, first_name, middle_name, last_name, email, phone, travel_date, purpose_of_visit) VALUES (:app,:num,:fn,:mn,:ln,:em,:ph,:td,:pv)");

    $stmt->execute([
        ':app'=>$_SESSION['application_id'], ':num'=>$travellerNum, ':fn'=>$data['first_name'],
        ':mn'=>$data['middle_name'], ':ln'=>$data['last_name'], ':em'=>$data['email'],
        ':ph'=>$data['phone'], ':td'=>$data['travel_date'], ':pv'=>$data['purpose_of_visit']
    ]);

    $_SESSION['traveller_ids'][$travellerNum] = (int)$db->lastInsertId();
}

jsonResponse(true, 'Contact details saved.', [
    'application_ref' => $_SESSION['application_ref'] ?? '',
    'traveller_id'    => $_SESSION['traveller_ids'][$travellerNum],
]);

