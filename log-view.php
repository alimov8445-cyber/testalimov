<?php
// log-view.php
$file = 'logs.json';

// Логика принудительной очистки логов
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    file_put_contents($file, json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header("Location: log-view.php"); // Перезагрузка страницы, чтобы сбросить POST-запрос
    exit;
}

$logs = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
if (is_array($logs)) {
    $logs = array_reverse($logs); // Свежие записи вверху списка
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
        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        h1 { margin: 0; color: #1a202c; }
        .btn-clear {
            background-color: #e53e3e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-clear:hover { background-color: #c53030; }
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }
        .meta-item {
            background: #edf2f7;
            padding: 10px 12px;
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
        .maps-link {
            display: inline-block;
            margin-top: 5px;
            background: #3182ce;
            color: white;
            text-decoration: none;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .maps-link:hover { background: #2b6cb0; }
    </style>
</head>
<body>

<div class="admin-container">
    <div class="header-box">
        <h1>Сетевой аудит и логирование</h1>
        <!-- Форма для очистки базы данных -->
        <form method="POST" onsubmit="return confirm('Вы уверены, что хотите полностью стереть все логи?');">
            <button type="submit" name="clear_logs" class="btn-clear">Очистить логи</button>
        </form>
    </div>
    
    <?php if (empty($logs)): ?>
        <p>База данных пуста. Откройте index.php для генерации первой записи.</p>
    <?php else: ?>
        <?php foreach ($logs as $log): ?>
            <div class="card">
                <div class="meta-grid">
                    <div class="meta-item"><strong>Время:</strong> <?php echo htmlspecialchars($log['time']); ?></div>
                    <div class="meta-item"><strong>IP-Адрес:</strong> <?php echo htmlspecialchars($log['ip']); ?></div>
                    <div class="meta-item"><strong>Сеть:</strong> <?php echo htmlspecialchars($log['port'] . ' (' . $log['protocol'] . ')'); ?></div>
                    
                    <div class="meta-item" style="background: #ebf8ff; border-left: 3px solid #3182ce;">
                        <strong>Координаты:</strong> <?php echo htmlspecialchars($log['lat'] . ', ' . $log['lon']); ?>
                        
                        <!-- Генерация ссылки на Google Карты, если координаты определены -->
                        <?php if (is_numeric($log['lat']) && is_numeric($log['lon'])): ?>
                            <br>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $log['lat']; ?>,<?php echo $log['lon']; ?>" 
                               target="_blank" 
                               class="maps-link">
                               Открыть в Google Maps ➔
                            </a>
                        <?php endif; ?>
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