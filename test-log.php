<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requireLogin();
sendSecurityHeaders(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(405);
    exit('Тестовую запись можно добавить только из защищённой POST-формы.');
}

$testIps = ['8.8.8.8', '1.1.1.1', '91.198.174.192'];
$id = 'test_' . bin2hex(random_bytes(6));
$entry = [
    'id' => $id,
    'time' => date('Y-m-d H:i:s'),
    'ip' => $testIps[array_rand($testIps)],
    'user_agent' => 'Test entry / Chrome',
    'protocol' => 'HTTPS',
    'port' => (string)random_int(40000, 60000),
    'lat' => 41.3111,
    'lon' => 69.2797,
    'gps_time' => date('Y-m-d H:i:s'),
    'gps_consent' => true,
    'geo' => [],
    'extra' => ['test' => true],
];
mutateLogs(static function (array &$logs) use ($id, $entry): void { $logs[$id] = $entry; });
$_SESSION['flash'] = ['message' => 'Тестовая запись добавлена.', 'type' => 'success'];
redirectTo('log-view.php');
