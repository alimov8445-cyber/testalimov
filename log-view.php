<?php
// log-view.php
session_start();
date_default_timezone_set('Asia/Tashkent');

$file = 'logs.json';

// Логика очистки логов
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    file_put_contents($file, json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header('Location: log-view.php');
    exit;
}

// Логика экспорта в CSV
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=network_logs_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID Сессии', 'Время', 'IP Адрес', 'User-Agent', 'Протокол', 'Порт', 'Lat', 'Lon']);
    if (file_exists($file)) {
        $logs = json_decode(file_get_contents($file), true);
        if (is_array($logs)) {
            $logs = array_reverse($logs);
            foreach ($logs as $log) {
                fputcsv($output, [$log['id']??'', $log['time']??'', $log['ip']??'', $log['user_agent']??'', $log['protocol']??'', $log['port']??'', $log['lat']??'', $log['lon']??'']);
            }
        }
    }
    fclose($output);
    exit;
}

// AJAX обработчик автообновления
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        echo is_array($data) ? json_encode(array_reverse($data)) : json_encode([]);
    } else {
        echo json_encode([]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TERMINAL // LOG_MONITOR</title>
    <style>
        :root {
            --bg-color: #0a0f0d; --panel-color: #111a16;
            --neon-green: #39ff14; --neon-blue: #00e5ff; --neon-red: #ff3366;
            --text-main: #d0e8db; --text-muted: #627d6f; --border-color: #1f3a2b;
        }
        body { background-color: var(--bg-color); color: var(--text-main); font-family: 'Courier New', monospace; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 15px; margin-bottom: 25px; }
        h1 { font-size: 22px; margin: 0; color: var(--neon-green); text-shadow: 0 0 8px rgba(57,255,20,0.3); letter-spacing: 2px; }
        .status-panel { display: flex; align-items: center; gap: 10px; }
        .live-indicator { width: 10px; height: 10px; background-color: var(--neon-green); border-radius: 50%; box-shadow: 0 0 10px var(--neon-green); animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(0.9); opacity: 0.6; } 50% { transform: scale(1.1); opacity: 1; } }
        .btn { background: transparent; padding: 8px 16px; font-family: inherit; font-size: 13px; cursor: pointer; text-decoration: none; text-transform: uppercase; transition: all 0.3s; }
        .btn-export { color: var(--neon-blue); border: 1px solid var(--neon-blue); }
        .btn-export:hover { background: var(--neon-blue); color: #000; box-shadow: 0 0 10px var(--neon-blue); }
        .btn-clear { color: var(--neon-red); border: 1px solid var(--neon-red); }
        .btn-clear:hover { background: var(--neon-red); color: #000; box-shadow: 0 0 10px var(--neon-red); }
        #logs-container { display: flex; flex-direction: column; gap: 15px; }
        .log-card { background-color: var(--panel-color); border: 1px solid var(--border-color); border-left: 4px solid var(--border-color); padding: 18px; }
        .log-card:hover { border-left-color: var(--neon-green); border-color: rgba(57,255,20,0.2); }
        .log-header { display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 8px; margin-bottom: 12px; }
        .log-ip { color: var(--neon-green); font-weight: bold; }
        .log-time { color: var(--text-muted); }
        .log-row { margin-bottom: 6px; font-size: 13px; }
        .label { color: var(--text-muted); display: inline-block; width: 110px; }
        .geo-link { color: var(--neon-blue); text-decoration: none; border-bottom: 1px dotted var(--neon-blue); }
        .no-data { text-align: center; padding: 40px; color: var(--text-muted); border: 1px dashed var(--border-color); }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div>
            <h1>// SYSTEM_LOG_MONITOR</h1>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">TIMEZONE: ASIA/TASHKENT</div>
        </div>
        <div class="status-panel">
            <span class="live-indicator"></span>
            <a href="?action=export" class="btn btn-export">Скачать .CSV</a>
            <a href="?action=clear" onclick="return confirm('Вы уверены, что хотите очистить все логи?');" class="btn btn-clear">Очистить логи</a>
        </div>
    </header>

    <div id="logs-container">
        <div class="no-data">Подключение к потоку логов...</div>
    </div>
</div>

<script>
let lastLogsJson = '';

function fetchLogs() {
    fetch('log-view.php?ajax=1')
        .then(response => response.json())
        .then(data => {
            const currentJson = JSON.stringify(data);
            if (currentJson === lastLogsJson) return;
            lastLogsJson = currentJson;

            const container = document.getElementById('logs-container');
            container.innerHTML = '';

            if (Object.keys(data).length === 0) {
                container.innerHTML = '<div class="no-data">[ БАЗА ДАННЫХ ПУСТА // ОЖИДАНИЕ ТРАФИКА ]</div>';
                return;
            }

            for (let id in data) {
                const log = data[id];
                let geoDisplay = '';
                
                if (log.lat !== 'Доступ отклонен или ожидается' && log.lon !== 'Доступ отклонен или ожидается') {
                    geoDisplay = `<a href="https://www.google.com/maps?q=${log.lat},${log.lon}" target="_blank" class="geo-link">ОТКРЫТЬ НА КАРТЕ [${log.lat}, ${log.lon}]</a>`;
                } else {
                    geoDisplay = `<span style="color: var(--neon-red);">${log.lat}</span>`;
                }

                const card = document.createElement('div');
                card.className = 'log-card';
                card.innerHTML = `
                    <div class="log-header">
                        <div class="log-ip">> IP: ${log.ip}</div>
                        <div class="log-time">[${log.time}]</div>
                    </div>
                    <div class="log-body">
                        <div class="log-row"><span class="label">USER-AGENT:</span><span>${log.user_agent}</span></div>
                        <div class="log-row"><span class="label">PROTOCOL:</span><span>${log.protocol} (PORT: ${log.port})</span></div>
                        <div class="log-row"><span class="label">GEOLOCATION:</span><span>${geoDisplay}</span></div>
                    </div>
                `;
                container.appendChild(card);
            }
        }).catch(err => console.error("Ошибка обновления:", err));
}

fetchLogs();
setInterval(fetchLogs, 2000);
</script>
</body>
</html>