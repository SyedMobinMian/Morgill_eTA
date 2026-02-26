<?php
/**
 * ============================================================
 * backend/get_cities.php
 * Dynamic City Loader: State ke basis pe cities nikalne ke liye.
 * Use: Jab user state dropdown change kare, toh cities update ho jayein.
 * ============================================================
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../core/bootstrap.php';

// Frontend se state_id pakdo (e.g., ?state_id=45)
$state_id = (int)($_GET['state_id'] ?? 0);

// Agar state_id missing hai toh bina database hit kiye khali array return kar do
if (!$state_id) { 
    echo json_encode([]); 
    exit; 
}

$db = getDB();

try {
    /**
     * Database se wahi cities uthao jo selected state se linked hain.
     * Order by name isliye taaki dropdown mein list thodi tameez se (A-Z) dikhe.
     */
    $stmt = $db->prepare("SELECT id, name FROM cities WHERE state_id = ? ORDER BY name");
    $stmt->execute([$state_id]);
    
    $rows = $stmt->fetchAll();
    
    // Result ko JSON mein convert karke bhej do frontend ko
    echo json_encode($rows);

} catch (PDOException $e) {
    // Agar server ya query mein koi panga hua toh yahan error log hoga
    error_log("Fetch Cities Error: " . $e->getMessage());
    echo json_encode(['error' => 'Could not load cities']);
}
