<?php
/**
 * ============================================================
 * backend/get_states.php
 * Dynamic State Loader: Country ke basis pe states fetch karta hai.
 * Use: Jab user country badalta hai, toh states dropdown update karne ke liye.
 * ============================================================
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../core/bootstrap.php';

// Frontend se aayi hui country_id pakdo (e.g., ?country_id=101)
$country_id = (int)($_GET['country_id'] ?? 0);

// Agar country_id hi nahi hai, toh khali array bhej ke kissa khatam karo
if (!$country_id) { 
    echo json_encode([]); 
    exit; 
}

$db = getDB();

try {
    /**
     * Database se sirf ID aur Name utha rahe hain states table se.
     * Order by name rakha hai taaki dropdown mein list sorted dikhe (A-Z).
     */
    $stmt = $db->prepare("SELECT id, name FROM states WHERE country_id = ? ORDER BY name");
    $stmt->execute([$country_id]);
    
    // Sab kuch fetch karke seedha JSON format mein print kar do
    echo json_encode($stmt->fetchAll());

} catch (PDOException $e) {
    // Agar DB mein koi panga hua toh error log karo
    error_log("Fetch States Error: " . $e->getMessage());
    echo json_encode(['error' => 'Could not load states']);
}
