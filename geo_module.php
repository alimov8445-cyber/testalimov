<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function emptyIpInfo(string $ip, string $status = 'unknown'): array
{
    return [
        'ip' => $ip,
        'country' => 'Не определено',
        'country_code' => '',
        'city' => 'Не определено',
        'isp' => 'Не определено',
        'lat' => null,
        'lon' => null,
        'vpn' => false,
        'proxy' => false,
        'tor' => false,
        'hosting' => false,
        'risk_known' => false,
        'status' => $status,
        'cached' => false,
    ];
}

function isPublicIp(string $ip): bool
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) !== false;
}

function getIpInfo(string $ip): array
{
    $ip = trim($ip);
    if (!isPublicIp($ip)) {
        $local = emptyIpInfo($ip, 'local');
        $local['country'] = 'Локальная сеть';
        $local['city'] = 'Локальный адрес';
        $local['isp'] = 'Локальная сеть';
        return $local;
    }

    $cacheFile = CACHE_DIR . '/' . hash('sha256', $ip) . '.json';
    $cacheTtl = max(300, (int)envValue('GEO_CACHE_TTL', '86400'));

    if (is_file($cacheFile) && (time() - (int)filemtime($cacheFile)) < $cacheTtl) {
        $cached = json_decode((string)file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            $cached['cached'] = true;
            return array_merge(emptyIpInfo($ip), $cached);
        }
    }

    $baseUrl = rtrim((string)envValue('IP_GEO_API_URL', 'https://ipwho.is'), '/');
    $url = $baseUrl . '/' . rawurlencode($ip) . '?lang=ru';
    $response = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => APP_NAME . '/' . APP_VERSION,
        ]);
        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if (is_string($body) && $body !== '' && $httpCode >= 200 && $httpCode < 300) {
            $response = $body;
        }
    } elseif (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 4,
                'header' => "Accept: application/json\r\nUser-Agent: " . APP_NAME . '/' . APP_VERSION . "\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if (is_string($body) && $body !== '') {
            $response = $body;
        }
    }

    if ($response === null) {
        return emptyIpInfo($ip, 'unavailable');
    }

    $data = json_decode($response, true);
    if (!is_array($data) || ($data['success'] ?? false) !== true) {
        return emptyIpInfo($ip, 'api_error');
    }

    $security = is_array($data['security'] ?? null) ? $data['security'] : [];
    $connection = is_array($data['connection'] ?? null) ? $data['connection'] : [];
    $riskKnown = $security !== [];

    $result = [
        'ip' => $ip,
        'country' => (string)($data['country'] ?? 'Не определено'),
        'country_code' => (string)($data['country_code'] ?? ''),
        'city' => (string)($data['city'] ?? 'Не определено'),
        'isp' => (string)($connection['isp'] ?? $connection['org'] ?? 'Не определено'),
        'lat' => validCoordinate($data['latitude'] ?? null, -90, 90),
        'lon' => validCoordinate($data['longitude'] ?? null, -180, 180),
        'vpn' => ($security['vpn'] ?? false) === true,
        'proxy' => ($security['proxy'] ?? false) === true,
        'tor' => ($security['tor'] ?? false) === true,
        'hosting' => ($security['hosting'] ?? false) === true,
        'risk_known' => $riskKnown,
        'status' => 'ok',
        'cached' => false,
    ];

    @file_put_contents(
        $cacheFile,
        json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );

    return $result;
}

function getLogGeo(array $log): array
{
    $stored = is_array($log['geo'] ?? null) ? $log['geo'] : [];
    if (!empty($stored['country']) || !empty($stored['city']) || !empty($stored['isp'])) {
        return array_merge(emptyIpInfo((string)($log['ip'] ?? '')), $stored, ['cached' => true]);
    }
    return getIpInfo((string)($log['ip'] ?? ''));
}

function getApproximateLocation(string $ip): array
{
    $info = getIpInfo($ip);
    $coords = ($info['lat'] !== null && $info['lon'] !== null)
        ? $info['lat'] . ', ' . $info['lon']
        : 'Не определены';

    return [
        'city' => $info['city'],
        'country' => $info['country'],
        'coords' => $coords,
        'isp' => $info['isp'],
    ];
}
