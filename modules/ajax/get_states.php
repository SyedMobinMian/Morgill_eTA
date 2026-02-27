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

function fetchRemoteJson(string $url, string $method = 'GET', ?array $payload = null): ?array {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
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
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        $countryStmt = $db->prepare("SELECT name FROM countries WHERE id = ? LIMIT 1");
        $countryStmt->execute([$country_id]);
        $countryName = (string)$countryStmt->fetchColumn();

        if ($countryName !== '') {
            $remote = fetchRemoteJson('https://countriesnow.space/api/v0.1/countries/states/q?country=' . rawurlencode($countryName), 'GET');
            if (is_array($remote) && empty($remote['error']) && !empty($remote['data']['states']) && is_array($remote['data']['states'])) {
                $checkStmt = $db->prepare("SELECT id FROM states WHERE country_id = :cid AND name = :name LIMIT 1");
                $insStmt = $db->prepare("INSERT INTO states (country_id, name) VALUES (:cid, :name)");
                foreach ($remote['data']['states'] as $st) {
                    $name = trim((string)($st['name'] ?? ''));
                    if ($name === '') continue;
                    $checkStmt->execute([':cid' => $country_id, ':name' => $name]);
                    if (!$checkStmt->fetchColumn()) {
                        $insStmt->execute([':cid' => $country_id, ':name' => $name]);
                    }
                }
                $stmt->execute([$country_id]);
                $rows = $stmt->fetchAll();
            }
        }
    }

    echo json_encode($rows);

} catch (PDOException $e) {
    // Agar DB mein koi panga hua toh error log karo
    error_log("Fetch States Error: " . $e->getMessage());
    echo json_encode(['error' => 'Could not load states']);
}
