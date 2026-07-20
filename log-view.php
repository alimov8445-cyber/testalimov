<<?php
// log-view.php
session_start();

// Установка временной зоны Узбекистана (Ташкент)
date_default_timezone_set('Asia/Tashkent');

$file = 'logs.json';

// Логика экспорта в CSV (скачивание файла)
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=network_logs_' . date('Ymd_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    // Добавляем BOM для корректного отображения кириллицы в Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['ID Сессии', 'Время (Ташкент)', 'IP Адрес', 'Штамп Устройства (User-Agent)', 'Протокол', 'Порт', 'Широта (Lat)', 'Долгота (Lon)']);
    
    if (file_exists($file)) {
        $logs = json_decode(file_get_contents($file), true);
        if (is_array($logs)) {
            // Разворачиваем массив, чтобы свежие логи были сверху
            $logs = array_reverse($logs);
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['id'] ?? '',
                    $log['time'] ?? '',
                    $log['ip'] ?? '',
                    $log['user_agent'] ?? '',
                    $log['protocol'] ?? '',
                    $log['port'] ?? '',
                    $log['lat'] ?? '',
                    $log['lon'] ?? ''
                ]);
            }
        }
    }
    fclose($output);
    exit;
}

// Получение логов для AJAX-запроса автообновления
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
            --bg-color: #0a0f0d;
            --panel-color: #111a16;
            --neon-green: #39ff14;
            --neon-blue: #00e5ff;
            --text-main: #d0e8db;
            --text-muted: #627d6f;
            --border-color: #1f3a2b;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Шапка терминала */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        h1 {
            font-size: 22px;
            margin: 0;
            color: var(--neon-green);
            text-shadow: 0 0 8px rgba(57, 255, 20, 0.3);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .status-panel {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Пульсирующая зеленая точка */
        .live-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: var(--neon-green);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--neon-green);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.9); opacity: 0.6; }
            50% { transform: scale(1.1); opacity: 1; box-shadow: 0 0 15px var(--neon-green); }
            100% { transform: scale(0.9); opacity: 0.6; }
        }

        .btn-export {
            background-color: transparent;
            color: var(--neon-blue);
            border: 1px solid var(--neon-blue);
            padding: 8px 16px;
            font-family: inherit;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            text-transform: uppercase;
            transition: all 0.3s ease;
            box-shadow: 0 0 5px rgba(0, 229, 255, 0.1);
        }

        .btn-export:hover {
            background-color: var(--neon-blue);
            color: #000;
            box-shadow: 0 0 12px var(--neon-blue);
        }

        /* Сетка для карточек логов */
        #logs-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        /* Стилизация карточки лога под консоль */
        .log-card {
            background-color: var(--panel-color);
            border-left: 4px solid var(--border-color);
            border-top: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            padding: 18px;
            position: relative;
            transition: border-color 0.3s ease;
        }

        .log-card:hover {
            border-left-color: var(--neon-green);
            border-color: rgba(57, 255, 20, 0.2);
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed var(--border-color);
            padding-bottom: 8px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .log-time {
            color: var(--text-muted);
        }

        .log-ip {
            color: var(--neon-green);
            font-weight: bold;
        }

        .log-body {
            font-size: 13px;
            line-height: 1.6;
        }

        .log-row {
            margin-bottom: 6px;
        }

        .label {
            color: var(--text-muted);
            display: inline-block;
            width: 110px;
        }

        .ua-text {
            color: var(--text-main);
            word-break: break-all;
        }

        /* Ссылка на координаты */
        .geo-link {
            color: var(--neon-blue);
            text-decoration: none;
            border-bottom: 1px dotted var(--neon-blue);
        }

        .geo-link:hover {
            color: #fff;
            border-bottom-style: solid;
            text-shadow: 0 0 5px var(--neon-blue);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
            border: 1px dashed var(--border-color);
        }
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
            <span style="font-size: 12px; color: var(--neon-green);">LIVE_STREAM</span>
            <a href="?action=export" class="btn-export">Скачать .CSV</a>
        </div>
    </header>

    <div id="logs-container">
        <!-- Сюда JavaScript будет автоматически рендерить логи -->
        <div class="no-data">Инициализация потока данных...</div>
    </div>
</div>

<script>
let lastLogsJson = '';

function fetchLogs() {
    fetch('log-view.php?ajax=1')
        .then(response => response.json())
        .then(data => {
            const currentJson = JSON.stringify(data);
            
            // Если данные не изменились, не перерисовываем страницу
            if (currentJson === lastLogsJson) return;
            lastLogsJson = currentJson;

            const container = document.getElementById('logs-container');
            container.innerHTML = '';

            if (Object.keys(data).length === 0) {
                container.innerHTML = '<div class="no-data">[ СИСТЕМА ЛОГОВ ПУСТА // ОЖИДАНИЕ КЛИКОВ ]</div>';
                return;
            }

            // Перебор пришедших записей
            for (let id in data) {
                const log = data[id];
                
                // Проверка координат
                let geoDisplay = '';
                if (log.lat !== 'Доступ отклонен или ожидается' && log.lon !== 'Доступ отклонен или ожидается') {
                    const mapUrl = `https://www.google.com/maps?q=${log.lat},${log.lon}`;
                    geoDisplay = `<a href="${mapUrl}" target="_blank" class="geo-link">MAPS_LINK [${log.lat}, ${log.lon}]</a>`;
                } else {
                    geoDisplay = `<span style="color: #ff3333;">${log.lat}</span>`;
                }

                const card = document.createElement('div');
                card.className = 'log-card';
                card.innerHTML = `
                    <div class="log-header">
                        <div class="log-ip">> IP: ${log.ip}</div>
                        <div class="log-time">[${log.time}]</div>
                    </div>
                    <div class="log-body">
                        <div class="log-row"><span class="label">AGENT:</span><span class="ua-text">${log.user_agent}</span></div>
                        <div class="log-row"><span class="label">PROTOCOL:</span><span>${log.protocol} (PORT: ${log.port})</span></div>
                        <div class="log-row"><span class="label">LOCATION:</span><span>${geoDisplay}</span></div>
                    </div>
                `;
                container.appendChild(card);
            }
        })
        .catch(error => console.error('Ошибка обновления потока логов:', error));
}

// Первичный вызов и запуск ежесекундного обновления
fetchLogs();
setInterval(fetchLogs, 1000);
</script>

</body>
</html>