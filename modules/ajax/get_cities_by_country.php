<?php
/**
 * Return cities belonging to a country (via states -> cities mapping).
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../core/bootstrap.php';

function fetchRemoteJson(string $url): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
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

    if (empty($out)) {
        $countryStmt = $db->prepare("SELECT name FROM countries WHERE id = :id LIMIT 1");
        $countryStmt->execute([':id' => $countryId]);
        $countryName = trim((string)$countryStmt->fetchColumn());
        if ($countryName !== '') {
            $remote = fetchRemoteJson('https://countriesnow.space/api/v0.1/countries/cities/q?country=' . rawurlencode($countryName));
            if (is_array($remote) && empty($remote['error']) && !empty($remote['data']) && is_array($remote['data'])) {
                $out = array_map(static function($name): array {
                    $n = trim((string)$name);
                    return ['id' => $n, 'name' => $n];
                }, array_filter($remote['data'], static fn($n) => trim((string)$n) !== ''));
            }
        }
    }

    echo json_encode($out);
} catch (PDOException $e) {
    error_log('Fetch Cities By Country Error: ' . $e->getMessage());
    echo json_encode([]);
}


