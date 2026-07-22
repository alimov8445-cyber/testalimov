<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requireLogin();
sendSecurityHeaders(true);

$points = [];
foreach (loadLogs() as $log) {
    if (!hasGpsCoordinates($log)) continue;
    $points[] = [
        'ip' => (string)($log['ip'] ?? ''),
        'time' => (string)($log['gps_time'] ?? $log['time'] ?? ''),
        'lat' => (float)$log['lat'],
        'lon' => (float)$log['lon'],
    ];
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Карта GPS — <?= h(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= h(appUrl('assets/app.css')) ?>">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIINfQ3ynhNCf/T+RM3S4aD8J90hGv+JjMZs=" crossorigin="anonymous">
  <script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand"><div class="brand-mark">N</div><div><strong>NET MONITOR</strong><small>Версия <?= h(APP_VERSION) ?></small></div></div>
    <nav class="nav">
      <a href="<?= h(appUrl('log-view.php')) ?>">▦ Журнал</a><a href="<?= h(appUrl('dashboard.php')) ?>">◫ Аналитика</a><a class="active" href="<?= h(appUrl('map.php')) ?>">⌖ Карта GPS</a><a href="<?= h(appUrl('index.php')) ?>" target="_blank" rel="noopener">↗ Публичная страница</a>
      <div class="nav-spacer"></div><form method="post" action="<?= h(appUrl('auth.php')) ?>"><input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="logout"><button class="danger" type="submit">↪ Выйти</button></form>
    </nav>
  </aside>
  <main class="main">
    <div class="container">
      <header class="topbar"><div><div class="eyebrow">Точные координаты</div><h1>Карта GPS</h1><p class="subtitle">Показаны только координаты, которые пользователь разрешил передать браузеру.</p></div><div class="actions"><span class="badge success"><?= count($points) ?> точек</span><a class="btn" href="<?= h(appUrl('log-view.php')) ?>">← К журналу</a></div></header>
      <section class="panel map-box"><div id="map"></div></section>
    </div>
  </main>
</div>
<nav class="mobile-nav"><a href="<?= h(appUrl('log-view.php')) ?>">▦<br>Логи</a><a href="<?= h(appUrl('dashboard.php')) ?>">◫<br>Графики</a><a class="active" href="<?= h(appUrl('map.php')) ?>">⌖<br>Карта</a><a href="<?= h(appUrl('index.php')) ?>">↗<br>Сайт</a></nav>
<script>
const points = <?= json_encode($points, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
window.addEventListener('DOMContentLoaded', () => {
  const map = L.map('map', { zoomControl: true }).setView([41.3111, 69.2797], points.length ? 5 : 4);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
  const bounds = [];
  points.forEach((point) => {
    const marker = L.marker([point.lat, point.lon]).addTo(map);
    const wrapper = document.createElement('div');
    const ip = document.createElement('strong'); ip.textContent = point.ip;
    const br = document.createElement('br');
    const time = document.createElement('span'); time.textContent = point.time;
    wrapper.append(ip, br, time);
    marker.bindPopup(wrapper);
    bounds.push([point.lat, point.lon]);
  });
  if (bounds.length === 1) map.setView(bounds[0], 13);
  if (bounds.length > 1) map.fitBounds(bounds, { padding: [30, 30] });
});
</script>
</body>
</html>
