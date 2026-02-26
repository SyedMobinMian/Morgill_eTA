<?php
/**
 * backend/ajax/get_lookups.php
 * Dropdown lookup API (schema-compatible).
 */

header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600');

session_start();
require_once __DIR__ . '/../../core/bootstrap.php';

$type = $_GET['type'] ?? '';
$db = getDB();

try {
    switch ($type) {
        case 'countries':
            $rows = $db->query("SELECT id, code, name FROM countries WHERE is_active = 1 ORDER BY name")->fetchAll();
            echo json_encode(['success' => true, 'data' => $rows]);
            break;

        case 'nationalities':
            // Fallback: use countries as nationalities list
            $rows = $db->query("SELECT id, name FROM countries WHERE is_active = 1 ORDER BY name")->fetchAll();
            echo json_encode(['success' => true, 'data' => $rows]);
            break;

        case 'purposes':
            $rows = $db->query("SELECT id, name FROM visit_purposes WHERE is_active = 1 ORDER BY name")->fetchAll();
            echo json_encode(['success' => true, 'data' => $rows]);
            break;

        case 'states':
            $countryId = (int)($_GET['country_id'] ?? 0);
            if ($countryId <= 0) {
                echo json_encode(['success' => true, 'data' => []]);
                break;
            }
            $stmt = $db->prepare("SELECT id, name FROM states WHERE country_id = :cid ORDER BY name");
            $stmt->execute([':cid' => $countryId]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'country_id_by_name':
            $name = trim((string)($_GET['name'] ?? ''));
            if ($name === '') {
                echo json_encode(['success' => true, 'id' => null]);
                break;
            }
            $stmt = $db->prepare("SELECT id FROM countries WHERE name = :name LIMIT 1");
            $stmt->execute([':name' => $name]);
            $row = $stmt->fetch();
            echo json_encode(['success' => true, 'id' => $row['id'] ?? null]);
            break;

        default:
            $countries = $db->query("SELECT id, code, name FROM countries WHERE is_active = 1 ORDER BY name")->fetchAll();
            $nationalities = $db->query("SELECT id, name FROM countries WHERE is_active = 1 ORDER BY name")->fetchAll();
            $purposes = $db->query("SELECT id, name FROM visit_purposes WHERE is_active = 1 ORDER BY name")->fetchAll();

            echo json_encode([
                'success' => true,
                'countries' => $countries,
                'nationalities' => $nationalities,
                'purposes' => $purposes,
            ]);
            break;
    }
} catch (Throwable $e) {
    error_log('Lookup API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lookup fetch failed.']);
}

