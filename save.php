<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

sendSecurityHeaders(true);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['status' => 'error', 'message' => 'Разрешён только POST-запрос.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
if (!validCsrf(is_string($csrf) ? $csrf : null)) {
    http_response_code(419);
    echo json_encode(['status' => 'error', 'message' => 'Сессия устарела. Обновите страницу.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$logId = (string)($_SESSION['current_log_id'] ?? '');
if ($logId === '') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Запись текущей сессии не найдена.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$lat = validCoordinate($_POST['lat'] ?? null, -90, 90);
$lon = validCoordinate($_POST['lon'] ?? null, -180, 180);
if ($lat === null || $lon === null) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Получены некорректные координаты.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $updated = mutateLogs(static function (array &$logs) use ($logId, $lat, $lon): bool {
        if (!isset($logs[$logId])) {
            return false;
        }
        $logs[$logId]['lat'] = round($lat, 6);
        $logs[$logId]['lon'] = round($lon, 6);
        $logs[$logId]['gps_time'] = date('Y-m-d H:i:s');
        $logs[$logId]['gps_consent'] = true;
        return true;
    });
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Не удалось сохранить координаты.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($updated !== true) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Запись журнала не найдена.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'Геопозиция сохранена с вашего разрешения.'], JSON_UNESCAPED_UNICODE);
