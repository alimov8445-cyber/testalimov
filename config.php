<?php
declare(strict_types=1);

session_start();

date_default_timezone_set('Asia/Tashkent');

/*
|--------------------------------------------------------------------------
| Основные настройки
|--------------------------------------------------------------------------
*/

define('APP_NAME', 'Network Log Monitor');
define('APP_VERSION', '2.0');

/*
|--------------------------------------------------------------------------
| Файлы
|--------------------------------------------------------------------------
*/

define('LOG_FILE', __DIR__ . '/logs.json');
define('CACHE_DIR', __DIR__ . '/cache');

/*
|--------------------------------------------------------------------------
| Администратор
|--------------------------------------------------------------------------
| Пароль позже можно изменить.
*/

define('ADMIN_LOGIN', 'admin');

/*
| Пароль: admin123
| Позже заменим на собственный.
*/

define(
    'ADMIN_PASSWORD_HASH',
    '$2y$10$3Qh1x3vwdnZY2m5v5EjkNeXxE7dF2vQ1M7dCjQK8oP6jvN2jQhL4K'
);

/*
|--------------------------------------------------------------------------
| Автообновление
|--------------------------------------------------------------------------
*/

define('AUTO_REFRESH', 2500);

/*
|--------------------------------------------------------------------------
| Создание папки cache
|--------------------------------------------------------------------------
*/

if (!is_dir(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0775, true);
}

/*
|--------------------------------------------------------------------------
| Создание logs.json
|--------------------------------------------------------------------------
*/

if (!file_exists(LOG_FILE)) {
    file_put_contents(
        LOG_FILE,
        json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

/*
|--------------------------------------------------------------------------
| Загрузка логов
|--------------------------------------------------------------------------
*/

function loadLogs(): array
{
    if (!file_exists(LOG_FILE)) {
        return [];
    }

    $json = file_get_contents(LOG_FILE);

    if ($json === false || trim($json) === '') {
        return [];
    }

    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}

/*
|--------------------------------------------------------------------------
| Сохранение логов
|--------------------------------------------------------------------------
*/

function saveLogs(array $logs): bool
{
    $fp = fopen(LOG_FILE, 'c+');

    if (!$fp) {
        return false;
    }

    flock($fp, LOCK_EX);

    ftruncate($fp, 0);

    rewind($fp);

    fwrite(
        $fp,
        json_encode(
            $logs,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        )
    );

    fflush($fp);

    flock($fp, LOCK_UN);

    fclose($fp);

    return true;
}

/*
|--------------------------------------------------------------------------
| Защита CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function csrf(): string
{
    return $_SESSION['csrf'];
}

function verifyCsrf(string $token): bool
{
    return hash_equals($_SESSION['csrf'], $token);
}