<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/geo_module.php';

requireLogin();
sendSecurityHeaders(true);

$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Сессия устарела.');
    }

    $action = (string)($_POST['action'] ?? '');
    if ($action === 'clear') {
        saveLogs([]);
        $_SESSION['flash'] = ['message' => 'Журнал полностью очищен.', 'type' => 'success'];
        redirectTo('log-view.php');
    }

    if ($action === 'refresh_geo') {
        $logsToUpdate = loadLogs();
        $updatedGeo = [];
        $processed = 0;
        foreach (array_reverse($logsToUpdate, true) as $id => $log) {
            $stored = is_array($log['geo'] ?? null) ? $log['geo'] : [];
            if (!empty($stored['country']) || $processed >= 5) {
                continue;
            }
            $info = getIpInfo((string)($log['ip'] ?? ''));
            $processed++;
            if ($info['status'] === 'ok' || $info['status'] === 'local') {
                $updatedGeo[$id] = $info;
            }
        }

        if ($updatedGeo !== []) {
            mutateLogs(static function (array &$logs) use ($updatedGeo): void {
                foreach ($updatedGeo as $id => $geo) {
                    if (isset($logs[$id])) {
                        $logs[$id]['geo'] = $geo;
                    }
                }
            });
        }

        $_SESSION['flash'] = [
            'message' => $updatedGeo !== []
                ? 'Геоданные обновлены для ' . count($updatedGeo) . ' записей.'
                : 'Новых геоданных для обновления нет или сервис временно недоступен.',
            'type' => $updatedGeo !== [] ? 'success' : 'warning',
        ];
        redirectTo('log-view.php');
    }
}

if (isset($_SESSION['flash']) && is_array($_SESSION['flash'])) {
    $flash = (string)($_SESSION['flash']['message'] ?? '');
    $flashType = (string)($_SESSION['flash']['type'] ?? 'success');
    unset($_SESSION['flash']);
}

if (($_GET['action'] ?? '') === 'export') {
    $logs = loadLogs();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=network_logs_' . date('Y-m-d_H-i') . '.csv');
    $out = fopen('php://output', 'wb');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Время', 'IP', 'Страна', 'Город', 'Провайдер', 'GPS согласие', 'Широта', 'Долгота', 'User-Agent']);
    foreach ($logs as $id => $log) {
        $geo = is_array($log['geo'] ?? null) ? $log['geo'] : [];
        fputcsv($out, [
            $id,
            $log['time'] ?? '',
            $log['ip'] ?? '',
            $geo['country'] ?? '',
            $geo['city'] ?? '',
            $geo['isp'] ?? '',
            !empty($log['gps_consent']) ? 'YES' : 'NO',
            hasGpsCoordinates($log) ? $log['lat'] : '',
            hasGpsCoordinates($log) ? $log['lon'] : '',
            $log['user_agent'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

$allLogs = array_reverse(loadLogs(), true);
$total = count($allLogs);
$uniqueIps = [];
$gpsCount = 0;
$riskCount = 0;
$countries = [];

foreach ($allLogs as $log) {
    $ip = trim((string)($log['ip'] ?? ''));
    if ($ip !== '') $uniqueIps[$ip] = true;
    if (hasGpsCoordinates($log)) $gpsCount++;
    $geo = is_array($log['geo'] ?? null) ? $log['geo'] : [];
    $country = trim((string)($geo['country'] ?? ''));
    if ($country !== '' && $country !== 'Не определено') $countries[$country] = true;
    if (!empty($geo['vpn']) || !empty($geo['proxy']) || !empty($geo['tor']) || !empty($geo['hosting'])) $riskCount++;
}

$query = trim((string)($_GET['q'] ?? ''));
$filtered = $allLogs;
if ($query !== '') {
    $needle = textLower($query);
    $filtered = array_filter($allLogs, static function (array $log) use ($needle): bool {
        $geo = is_array($log['geo'] ?? null) ? $log['geo'] : [];
        $haystack = textLower(implode(' ', [
            $log['time'] ?? '', $log['ip'] ?? '', $log['user_agent'] ?? '',
            $geo['country'] ?? '', $geo['city'] ?? '', $geo['isp'] ?? '',
        ]));
        return str_contains($haystack, $needle);
    });
}

$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$pages = max(1, (int)ceil(count($filtered) / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
$pageLogs = array_slice($filtered, $offset, $perPage, true);

function browserLabel(string $ua): string
{
    if (stripos($ua, 'Edg/') !== false) return 'Microsoft Edge';
    if (stripos($ua, 'Chrome/') !== false) return 'Chrome';
    if (stripos($ua, 'Firefox/') !== false) return 'Firefox';
    if (stripos($ua, 'Safari/') !== false) return 'Safari';
    return $ua !== '' ? 'Другой браузер' : 'Не определён';
}

function pageLink(int $page, string $query): string
{
    $params = ['page' => $page];
    if ($query !== '') $params['q'] = $query;
    return appUrl('log-view.php?' . http_build_query($params));
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Журнал — <?= h(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= h(appUrl('assets/app.css')) ?>">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand"><div class="brand-mark">N</div><div><strong>NET MONITOR</strong><small>Версия <?= h(APP_VERSION) ?></small></div></div>
    <nav class="nav">
      <a class="active" href="<?= h(appUrl('log-view.php')) ?>">▦ Журнал</a>
      <a href="<?= h(appUrl('dashboard.php')) ?>">◫ Аналитика</a>
      <a href="<?= h(appUrl('map.php')) ?>">⌖ Карта GPS</a>
      <a href="<?= h(appUrl('index.php')) ?>" target="_blank" rel="noopener">↗ Публичная страница</a>
      <div class="nav-spacer"></div>
      <a href="<?= h(appUrl('log-view.php?action=export')) ?>">↓ Экспорт CSV</a>
      <form method="post" action="<?= h(appUrl('auth.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="logout">
        <button class="danger" type="submit">↪ Выйти</button>
      </form>
    </nav>
  </aside>

  <main class="main">
    <div class="container">
      <header class="topbar">
        <div><div class="eyebrow">Мониторинг подключений</div><h1>Журнал событий</h1><p class="subtitle">Публичный адрес: <span class="mono"><?= h(APP_URL) ?></span></p></div>
        <div class="actions">
          <form method="post" action="<?= h(appUrl('log-view.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="refresh_geo">
            <button class="btn" type="submit">⌁ Обновить GEO (до 5)</button>
          </form>
          <form method="post" action="<?= h(appUrl('log-view.php')) ?>" onsubmit="return confirm('Удалить все записи журнала?')">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="clear">
            <button class="btn danger" type="submit">⌫ Очистить</button>
          </form>
        </div>
      </header>

      <?php if (isUsingDefaultAdminPassword()): ?>
        <div class="notice"><strong>Важно:</strong> используется стандартный пароль. В Railway добавьте переменную <span class="mono">ADMIN_PASSWORD</span> с новым сложным паролем.</div>
      <?php endif; ?>
      <?php if ($flash !== ''): ?><div class="notice" style="<?= $flashType === 'warning' ? '' : 'border-color:rgba(52,211,153,.28);background:rgba(6,78,59,.22);color:#a7f3d0' ?>"><?= h($flash) ?></div><?php endif; ?>

      <section class="grid">
        <article class="card metric"><div class="label">Все события</div><div class="value"><?= $total ?></div><div class="hint">За весь период хранения</div></article>
        <article class="card metric"><div class="label">Уникальные IP</div><div class="value"><?= count($uniqueIps) ?></div><div class="hint">По текущему журналу</div></article>
        <article class="card metric"><div class="label">GPS с согласием</div><div class="value"><?= $gpsCount ?></div><div class="hint">Точные координаты</div></article>
        <article class="card metric"><div class="label">Известные страны</div><div class="value"><?= count($countries) ?></div><div class="hint"><?= $riskCount ?> сигналов риска</div></article>
      </section>

      <section class="panel">
        <div class="panel-head">
          <div><h2>Последние подключения</h2><div class="muted" style="margin-top:6px;font-size:13px">Найдено: <?= count($filtered) ?></div></div>
          <form method="get" action="<?= h(appUrl('log-view.php')) ?>" style="display:flex;gap:8px;width:min(470px,100%)">
            <input class="search" name="q" value="<?= h($query) ?>" placeholder="IP, страна, город, браузер…">
            <button class="btn small" type="submit">Найти</button>
          </form>
        </div>

        <?php if ($pageLogs === []): ?>
          <div class="empty">Записи не найдены.</div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Время</th><th>IP / браузер</th><th>География</th><th>Провайдер</th><th>Риск</th><th>GPS</th></tr></thead>
            <tbody>
            <?php foreach ($pageLogs as $log): $geo = is_array($log['geo'] ?? null) ? array_merge(emptyIpInfo((string)($log['ip'] ?? '')), $log['geo']) : emptyIpInfo((string)($log['ip'] ?? '')); ?>
              <tr>
                <td><div><?= h($log['time'] ?? '') ?></div><div class="muted" style="font-size:12px;margin-top:4px"><?= h($log['protocol'] ?? '') ?></div></td>
                <td><div class="mono"><?= h($log['ip'] ?? '') ?></div><div class="muted" title="<?= h($log['user_agent'] ?? '') ?>" style="font-size:12px;margin-top:5px"><?= h(browserLabel((string)($log['user_agent'] ?? ''))) ?></div></td>
                <td><div><?= h($geo['country']) ?></div><div class="muted" style="font-size:12px;margin-top:4px"><?= h($geo['city']) ?></div></td>
                <td><?= h($geo['isp']) ?></td>
                <td>
                  <?php if (!$geo['risk_known']): ?><span class="badge neutral">Нет данных</span>
                  <?php elseif ($geo['vpn'] || $geo['proxy'] || $geo['tor'] || $geo['hosting']): ?><span class="badge danger">Обнаружен</span>
                  <?php else: ?><span class="badge success">Чисто</span><?php endif; ?>
                </td>
                <td>
                  <?php if (hasGpsCoordinates($log)): $coords = $log['lat'] . ',' . $log['lon']; ?>
                    <a class="btn small" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps?q=<?= h(rawurlencode($coords)) ?>">Открыть карту</a>
                  <?php else: ?><span class="badge neutral">Не предоставлен</span><?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <div class="pagination">
          <span>Страница <?= $page ?> из <?= $pages ?></span>
          <div class="actions">
            <?php if ($page > 1): ?><a class="btn small" href="<?= h(pageLink($page - 1, $query)) ?>">← Назад</a><?php endif; ?>
            <?php if ($page < $pages): ?><a class="btn small" href="<?= h(pageLink($page + 1, $query)) ?>">Вперёд →</a><?php endif; ?>
          </div>
        </div>
      </section>
    </div>
  </main>
</div>
<nav class="mobile-nav"><a class="active" href="<?= h(appUrl('log-view.php')) ?>">▦<br>Логи</a><a href="<?= h(appUrl('dashboard.php')) ?>">◫<br>Графики</a><a href="<?= h(appUrl('map.php')) ?>">⌖<br>Карта</a><a href="<?= h(appUrl('index.php')) ?>">↗<br>Сайт</a></nav>
</body>
</html>
