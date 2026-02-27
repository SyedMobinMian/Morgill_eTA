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

function fetchRemoteJson(string $url, string $method = 'GET', ?array $payload = null): ?array {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ];
    if (strtoupper($method) === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_HTTPHEADER] = ['Accept: application/json', 'Content-Type: application/json'];
        $opts[CURLOPT_POSTFIELDS] = json_encode($payload ?? []);
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err || $code < 200 || $code >= 300 || !$raw) {
        return null;
    }
    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
}

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

    if (empty($rows)) {
        $metaStmt = $db->prepare("
            SELECT s.name AS state_name, c.name AS country_name
            FROM states s
            INNER JOIN countries c ON c.id = s.country_id
            WHERE s.id = :sid
            LIMIT 1
        ");
        $metaStmt->execute([':sid' => $state_id]);
        $meta = $metaStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stateName = trim((string)($meta['state_name'] ?? ''));
        $countryName = trim((string)($meta['country_name'] ?? ''));

        if ($stateName !== '' && $countryName !== '') {
            $remote = fetchRemoteJson(
                'https://countriesnow.space/api/v0.1/countries/state/cities',
                'POST',
                ['country' => $countryName, 'state' => $stateName]
            );
            if (is_array($remote) && empty($remote['error']) && !empty($remote['data']) && is_array($remote['data'])) {
                $checkStmt = $db->prepare("SELECT id FROM cities WHERE state_id = :sid AND name = :name LIMIT 1");
                $insStmt = $db->prepare("INSERT INTO cities (state_id, name) VALUES (:sid, :name)");
                foreach ($remote['data'] as $cityNameRaw) {
                    $cityName = trim((string)$cityNameRaw);
                    if ($cityName === '') continue;
                    $checkStmt->execute([':sid' => $state_id, ':name' => $cityName]);
                    if (!$checkStmt->fetchColumn()) {
                        $insStmt->execute([':sid' => $state_id, ':name' => $cityName]);
                    }
                }
                $stmt->execute([$state_id]);
                $rows = $stmt->fetchAll();
            }
        }
    }

    echo json_encode($rows);

} catch (PDOException $e) {
    // Agar server ya query mein koi panga hua toh yahan error log hoga
    error_log("Fetch Cities Error: " . $e->getMessage());
    echo json_encode(['error' => 'Could not load cities']);
}
