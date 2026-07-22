<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/geo_module.php';

requireLogin();
sendSecurityHeaders(true);

$logs = loadLogs();
$total = count($logs);
$uniqueIps = [];
$gpsCount = 0;
$countries = [];
$hours = array_fill(0, 24, 0);
$days = [];

for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime('-' . $i . ' days'));
    $days[$day] = 0;
}

foreach ($logs as $log) {
    $ip = trim((string)($log['ip'] ?? ''));
    if ($ip !== '') $uniqueIps[$ip] = true;
    if (hasGpsCoordinates($log)) $gpsCount++;

    $timestamp = strtotime((string)($log['time'] ?? ''));
    if ($timestamp !== false) {
        $hours[(int)date('G', $timestamp)]++;
        $day = date('Y-m-d', $timestamp);
        if (array_key_exists($day, $days)) $days[$day]++;
    }

    $geo = is_array($log['geo'] ?? null) ? $log['geo'] : [];
    $country = trim((string)($geo['country'] ?? ''));
    if ($country !== '' && $country !== 'Не определено') {
        $countries[$country] = ($countries[$country] ?? 0) + 1;
    }
}
arsort($countries);
$topCountries = array_slice($countries, 0, 8, true);
$peakHour = array_search(max($hours), $hours, true);
$todayCount = $days[date('Y-m-d')] ?? 0;
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Аналитика — <?= h(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= h(appUrl('assets/app.css')) ?>">
  <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand"><div class="brand-mark">N</div><div><strong>NET MONITOR</strong><small>Версия <?= h(APP_VERSION) ?></small></div></div>
    <nav class="nav">
      <a href="<?= h(appUrl('log-view.php')) ?>">▦ Журнал</a>
      <a class="active" href="<?= h(appUrl('dashboard.php')) ?>">◫ Аналитика</a>
      <a href="<?= h(appUrl('map.php')) ?>">⌖ Карта GPS</a>
      <a href="<?= h(appUrl('index.php')) ?>" target="_blank" rel="noopener">↗ Публичная страница</a>
      <div class="nav-spacer"></div>
      <form method="post" action="<?= h(appUrl('auth.php')) ?>"><input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="logout"><button class="danger" type="submit">↪ Выйти</button></form>
    </nav>
  </aside>

  <main class="main">
    <div class="container">
      <header class="topbar">
        <div><div class="eyebrow">Сводка данных</div><h1>Аналитика</h1><p class="subtitle">Динамика посещений и география по сохранённому журналу.</p></div>
        <div class="actions"><a class="btn" href="<?= h(appUrl('log-view.php')) ?>">← К журналу</a><a class="btn primary" href="<?= h(appUrl('log-view.php?action=export')) ?>">↓ Скачать CSV</a></div>
      </header>

      <section class="grid">
        <article class="card metric"><div class="label">Сегодня</div><div class="value"><?= $todayCount ?></div><div class="hint">Новых событий</div></article>
        <article class="card metric"><div class="label">Всего</div><div class="value"><?= $total ?></div><div class="hint">Записей в хранилище</div></article>
        <article class="card metric"><div class="label">Уникальные IP</div><div class="value"><?= count($uniqueIps) ?></div><div class="hint">За весь период</div></article>
        <article class="card metric"><div class="label">Пиковый час</div><div class="value"><?= str_pad((string)$peakHour, 2, '0', STR_PAD_LEFT) ?>:00</div><div class="hint"><?= max($hours) ?> событий</div></article>
      </section>

      <section class="chart-grid">
        <article class="panel chart-box"><div class="panel-head"><div><h2>Последние 7 дней</h2><div class="muted" style="font-size:13px;margin-top:5px">Количество событий по дням</div></div></div><canvas id="daysChart"></canvas></article>
        <article class="panel chart-box"><div class="panel-head"><div><h2>География</h2><div class="muted" style="font-size:13px;margin-top:5px">Топ стран с обновлённым GEO</div></div></div><canvas id="countriesChart"></canvas></article>
      </section>

      <section class="panel">
        <div class="panel-head"><div><h2>Активность по часам</h2><div class="muted" style="font-size:13px;margin-top:5px">Распределение по часовому поясу Asia/Tashkent</div></div><span class="badge success"><?= $gpsCount ?> GPS‑точек</span></div>
        <canvas id="hoursChart" height="95"></canvas>
      </section>
    </div>
  </main>
</div>
<nav class="mobile-nav"><a href="<?= h(appUrl('log-view.php')) ?>">▦<br>Логи</a><a class="active" href="<?= h(appUrl('dashboard.php')) ?>">◫<br>Графики</a><a href="<?= h(appUrl('map.php')) ?>">⌖<br>Карта</a><a href="<?= h(appUrl('index.php')) ?>">↗<br>Сайт</a></nav>
<script>
window.addEventListener('DOMContentLoaded', () => {
  Chart.defaults.color = '#94a3b8';
  Chart.defaults.borderColor = 'rgba(148,163,184,.12)';

  new Chart(document.getElementById('daysChart'), {
    type: 'line',
    data: { labels: <?= json_encode(array_map(static fn($d) => date('d.m', strtotime($d)), array_keys($days)), JSON_UNESCAPED_UNICODE) ?>, datasets: [{ label: 'События', data: <?= json_encode(array_values($days)) ?>, borderColor: '#22d3ee', backgroundColor: 'rgba(34,211,238,.12)', fill: true, tension: .35, pointRadius: 3 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
  });

  new Chart(document.getElementById('countriesChart'), {
    type: 'doughnut',
    data: { labels: <?= json_encode(array_keys($topCountries), JSON_UNESCAPED_UNICODE) ?>, datasets: [{ data: <?= json_encode(array_values($topCountries)) ?>, backgroundColor: ['#22d3ee','#3b82f6','#8b5cf6','#34d399','#fbbf24','#fb7185','#60a5fa','#a78bfa'], borderWidth: 0 }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '66%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 16 } } } }
  });

  new Chart(document.getElementById('hoursChart'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_map(static fn($h) => str_pad((string)$h, 2, '0', STR_PAD_LEFT), array_keys($hours))) ?>, datasets: [{ label: 'События', data: <?= json_encode(array_values($hours)) ?>, backgroundColor: 'rgba(59,130,246,.65)', borderRadius: 7 }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
  });
});
</script>
</body>
</html>
