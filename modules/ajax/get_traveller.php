<?php
/**
 * ============================================================
 * backend/get_traveller.php
 * Form edit karne ke liye purana saved data fetch karna.
 * Frontend isi se fields ko auto-fill karta hai.
 * ============================================================
 */

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../core/bootstrap.php';

// Check karo session zinda hai ya nahi
if (empty($_SESSION['application_id'])) {
    jsonResponse(false, 'Session expired.');
}

// URL se traveller number uthao (e.g., ?traveller_num=1)
$travellerNum  = (int)($_GET['traveller_num'] ?? 1);
$travellerDbId = $_SESSION['traveller_ids'][$travellerNum] ?? null;

if (!$travellerDbId) {
    jsonResponse(false, 'Traveller not found.');
}

$db = getDB();

try {
    // Database se us specific traveller ki saari details nikaalo
    $stmt = $db->prepare("SELECT * FROM travellers WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $travellerDbId]);
    $row  = $stmt->fetch();

    if (!$row) {
        jsonResponse(false, 'Traveller record not found in DB.');
    }

    /**
     * Data Cleaning for Frontend:
     * Internal IDs ya sensitive cheezein hata do jo frontend ko nahi chahiye.
     */
    unset($row['application_id']);
    // $row['id'] ko rehne dete hain taaki frontend ko pata ho kis ID pe update maarna hai

    // Sab mil gaya, data bhej do
    jsonResponse(true, 'OK', ['traveller' => $row]);

} catch (PDOException $e) {
    error_log("Fetch Traveller Error: " . $e->getMessage());
    jsonResponse(false, 'Database error while fetching data.');
}
