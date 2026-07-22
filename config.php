<?php
declare(strict_types=1);

function envValue(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

function requestIsHttps(): bool
{
    $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    return $forwardedProto === 'https'
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => requestIsHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set('Asia/Tashkent');

define('APP_NAME', envValue('APP_NAME', 'Network Log Monitor'));
define('APP_VERSION', '3.1');

$configuredUrl = trim((string)envValue('APP_URL', ''));
$railwayDomain = trim((string)envValue('RAILWAY_PUBLIC_DOMAIN', ''));

if ($configuredUrl !== '') {
    $publicUrl = $configuredUrl;
} elseif ($railwayDomain !== '') {
    $publicUrl = 'https://' . $railwayDomain;
} else {
    $host = preg_replace('/[^a-zA-Z0-9.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $publicUrl = (requestIsHttps() ? 'https' : 'http') . '://' . ($host ?: 'localhost');
}

define('APP_URL', rtrim($publicUrl, '/'));
define('ADMIN_LOGIN', (string)envValue('ADMIN_LOGIN', 'admin'));
define('ADMIN_PASSWORD', (string)envValue('ADMIN_PASSWORD', 'admin123'));
define('ADMIN_PASSWORD_HASH', (string)envValue('ADMIN_PASSWORD_HASH', ''));

$preferredDataDir = (string)envValue('DATA_DIR', '');
if ($preferredDataDir === '') {
    $preferredDataDir = is_dir('/data') && is_writable('/data') ? '/data' : __DIR__ . '/data';
}

if (!is_dir($preferredDataDir) && !@mkdir($preferredDataDir, 0775, true) && !is_dir($preferredDataDir)) {
    $preferredDataDir = __DIR__;
}

define('DATA_DIR', $preferredDataDir);
define('LOG_FILE', DATA_DIR . '/logs.json');
define('CACHE_DIR', DATA_DIR . '/cache');

if (!is_dir(CACHE_DIR)) {
    @mkdir(CACHE_DIR, 0775, true);
}

$legacyLogFile = __DIR__ . '/logs.json';
if (!file_exists(LOG_FILE)) {
    if ($legacyLogFile !== LOG_FILE && file_exists($legacyLogFile)) {
        @copy($legacyLogFile, LOG_FILE);
    }
    if (!file_exists(LOG_FILE)) {
        @file_put_contents(LOG_FILE, "[]\n", LOCK_EX);
    }
}

function appUrl(string $path = ''): string
{
    $path = ltrim($path, '/');
    return APP_URL . ($path === '' ? '' : '/' . $path);
}

function redirectTo(string $path): never
{
    header('Location: ' . appUrl($path));
    exit;
}

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function textLower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function textCut(string $value, int $length): string
{
    return function_exists('mb_substr') ? mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function validCsrf(?string $token): bool
{
    return is_string($token)
        && $token !== ''
        && hash_equals(csrfToken(), $token);
}

function sendSecurityHeaders(bool $private = false): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: geolocation=(self), camera=(), microphone=()');
    if ($private) {
        header('Cache-Control: no-store, private, max-age=0');
        header('Pragma: no-cache');
    }
}

function isUsingDefaultAdminPassword(): bool
{
    return ADMIN_PASSWORD_HASH === '' && ADMIN_PASSWORD === 'admin123';
}

function normalizeLogs(mixed $data): array
{
    if (!is_array($data)) {
        return [];
    }

    if (array_key_exists('ip', $data) || array_key_exists('time', $data)) {
        $hasContent = trim((string)($data['ip'] ?? '')) !== ''
            || trim((string)($data['time'] ?? '')) !== '';
        $data = $hasContent ? [$data] : [];
    }

    $normalized = [];
    foreach ($data as $key => $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = trim((string)($row['id'] ?? $key));
        if ($id === '') {
            $id = bin2hex(random_bytes(8));
        }
        $row['id'] = $id;
        $normalized[$id] = $row;
    }

    return $normalized;
}

function decodeLogContents(string $contents): array
{
    if (trim($contents) === '') {
        return [];
    }
    return normalizeLogs(json_decode($contents, true));
}

function loadLogs(): array
{
    $handle = @fopen(LOG_FILE, 'c+');
    if ($handle === false) {
        return [];
    }

    try {
        if (!flock($handle, LOCK_SH)) {
            return [];
        }
        rewind($handle);
        $contents = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        return decodeLogContents($contents === false ? '' : $contents);
    } finally {
        fclose($handle);
    }
}

function saveLogs(array $logs): bool
{
    $handle = @fopen(LOG_FILE, 'c+');
    if ($handle === false) {
        return false;
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            return false;
        }
        $json = json_encode(normalizeLogs($logs), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            flock($handle, LOCK_UN);
            return false;
        }
        rewind($handle);
        ftruncate($handle, 0);
        $written = fwrite($handle, $json . PHP_EOL);
        fflush($handle);
        flock($handle, LOCK_UN);
        return $written !== false;
    } finally {
        fclose($handle);
    }
}

function mutateLogs(callable $mutator): mixed
{
    $handle = @fopen(LOG_FILE, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Не удалось открыть хранилище логов.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Не удалось заблокировать хранилище логов.');
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        $logs = decodeLogContents($contents === false ? '' : $contents);
        $result = $mutator($logs);

        $json = json_encode(normalizeLogs($logs), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Не удалось сформировать JSON.');
        }

        rewind($handle);
        ftruncate($handle, 0);
        if (fwrite($handle, $json . PHP_EOL) === false) {
            throw new RuntimeException('Не удалось сохранить логи.');
        }
        fflush($handle);
        flock($handle, LOCK_UN);

        return $result;
    } finally {
        fclose($handle);
    }
}

function clientIp(): string
{
    $candidates = [];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $candidates = array_merge($candidates, explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']));
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $candidates[] = (string)$_SERVER['HTTP_X_REAL_IP'];
    }
    $candidates[] = (string)($_SERVER['REMOTE_ADDR'] ?? '');

    foreach ($candidates as $candidate) {
        $candidate = trim($candidate);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }
    }

    return 'Не определен';
}

function validCoordinate(mixed $value, float $min, float $max): ?float
{
    if (!is_numeric($value)) {
        return null;
    }
    $number = (float)$value;
    return ($number >= $min && $number <= $max) ? $number : null;
}

function hasGpsCoordinates(array $log): bool
{
    return validCoordinate($log['lat'] ?? null, -90, 90) !== null
        && validCoordinate($log['lon'] ?? null, -180, 180) !== null;
}
