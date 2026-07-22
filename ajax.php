<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requireLogin();
sendSecurityHeaders(true);
header('Content-Type: application/json; charset=utf-8');

$action = (string)($_GET['action'] ?? 'count');
$logs = loadLogs();

switch ($action) {
    case 'count':
        echo json_encode(['count' => count($logs)], JSON_UNESCAPED_UNICODE);
        break;

    case 'logs':
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));
        $items = array_slice(array_reverse($logs, true), 0, $limit, true);
        echo json_encode(['logs' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'stats':
        $ips = [];
        $gps = 0;
        $countries = [];
        foreach ($logs as $log) {
            $ip = trim((string)($log['ip'] ?? ''));
            if ($ip !== '') $ips[$ip] = true;
            if (hasGpsCoordinates($log)) $gps++;
            $geo = is_array($log['geo'] ?? null) ? $log['geo'] : [];
            $country = trim((string)($geo['country'] ?? ''));
            if ($country !== '') $countries[$country] = ($countries[$country] ?? 0) + 1;
        }
        echo json_encode([
            'total' => count($logs),
            'unique_ips' => count($ips),
            'gps_count' => $gps,
            'countries' => $countries,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Неизвестное действие.'], JSON_UNESCAPED_UNICODE);
}
