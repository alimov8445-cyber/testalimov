<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

sendSecurityHeaders(false);
header('Content-Type: application/json; charset=utf-8');
$storageWritable = is_dir(DATA_DIR) && is_writable(DATA_DIR) && is_writable(LOG_FILE);
http_response_code($storageWritable ? 200 : 503);
echo json_encode([
    'status' => $storageWritable ? 'ok' : 'degraded',
    'app' => APP_NAME,
    'version' => APP_VERSION,
    'storage' => $storageWritable ? 'writable' : 'not_writable',
    'time' => date(DATE_ATOM),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
