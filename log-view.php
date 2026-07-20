<?php
// log-view.php
$file = 'logs.json';
$logs = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
// Переворачиваем массив, чтобы новые записи были вверху страницы
if (is_array($logs)) {
    $logs = array_reverse($logs);
} else {
    $logs = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель мониторинга запросов</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background-color: #f7fafc;
            color: #2d3748;
            margin: 0;
            padding: 20px;
        }
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 { color: #1a202c; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 15px;
            margin-bottom: 15px;
            border-left: 5px solid #4a5568;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }
        .meta-item {
            background: #edf2f7;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .meta-item strong { color: #4a5568; }
        .ua-block {
            font-family: monospace;
            background: #1a202c;
            color: #38a169;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            word-break: break-all;
        }
    </style>
</head>
<body>

<div class="admin-container">
    <h1>Логи посещений (Зафиксированные конфигурации)</h1>
    
    <?php if (empty($logs)): ?>
        <p>Записей пока нет. Откройте index.php, чтобы сгенерировать лог.</p>
    <?php else: ?>
        <?php foreach ($logs as $log): ?>
            <div class="card">
                <div class="meta-grid">
                    <div class="meta-item"><strong>Время:</strong> <?php echo htmlspecialchars($log['time']); ?></div>
                    <div class="meta-item"><strong>IP-Адрес:</strong> <?php echo htmlspecialchars($log['ip']); ?></div>
                    <div class="meta-item"><strong>Порт / Протокол:</strong> <?php echo htmlspecialchars($log['port'] . ' (' . $log['protocol'] . ')'); ?></div>
                    <div class="meta-item" style="background: #ebf8ff; border-left: 3px solid #3182ce;">
                        <strong>Координаты:</strong> <?php echo htmlspecialchars($log['lat'] . ', ' . $log['lon']); ?>
                    </div>
                </div>
                <div class="ua-block">
                    <strong>User-Agent:</strong> <?php echo htmlspecialchars($log['user_agent']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>