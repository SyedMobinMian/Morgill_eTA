<?php
/**
 * Return cities belonging to a country (via states -> cities mapping).
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../core/bootstrap.php';

$countryId = (int)($_GET['country_id'] ?? 0);
if ($countryId <= 0) {
    echo json_encode([]);
    exit;
}

$db = getDB();

try {
    $stmt = $db->prepare("
        SELECT DISTINCT c.name
        FROM cities c
        INNER JOIN states s ON s.id = c.state_id
        WHERE s.country_id = :country_id
        ORDER BY c.name
    ");
    $stmt->execute([':country_id' => $countryId]);
    $rows = $stmt->fetchAll();

    $out = array_map(static function(array $row): array {
        return ['id' => $row['name'], 'name' => $row['name']];
    }, $rows);

    echo json_encode($out);
} catch (PDOException $e) {
    error_log('Fetch Cities By Country Error: ' . $e->getMessage());
    echo json_encode([]);
}


