<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function addLog(array $data = []): string
{
    $id = bin2hex(random_bytes(10));
    $protocol = (string)($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP');
    if (requestIsHttps()) {
        $protocol .= ' / HTTPS';
    }

    $entry = [
        'id' => $id,
        'time' => date('Y-m-d H:i:s'),
        'ip' => clientIp(),
        'user_agent' => textCut((string)($_SERVER['HTTP_USER_AGENT'] ?? 'Не определен'), 600),
        'protocol' => $protocol,
        'port' => (string)($_SERVER['REMOTE_PORT'] ?? ''),
        'lat' => null,
        'lon' => null,
        'gps_time' => null,
        'gps_consent' => false,
        'geo' => [],
        'extra' => $data,
    ];

    mutateLogs(static function (array &$logs) use ($id, $entry): void {
        $logs[$id] = $entry;
        $maxEntries = max(100, (int)envValue('LOG_MAX_ENTRIES', '5000'));
        if (count($logs) > $maxEntries) {
            $logs = array_slice($logs, -$maxEntries, null, true);
        }
    });

    $_SESSION['current_log_id'] = $id;
    return $id;
}

function ensureCurrentLog(array $data = []): string
{
    $currentId = (string)($_SESSION['current_log_id'] ?? '');
    if ($currentId !== '') {
        $logs = loadLogs();
        if (isset($logs[$currentId])) {
            return $currentId;
        }
    }

    return addLog($data);
}
