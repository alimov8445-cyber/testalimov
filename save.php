<?php
// save.php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['current_log_id'])) {
    $log_id = $_SESSION['current_log_id'];
    $lat = $_POST['lat'] ?? 'Не определена';
    $lon = $_POST['lon'] ?? 'Не определена';

    $file = 'logs.json';
    
    if (file_exists($file)) {
        $current_data = json_decode(file_get_contents($file), true);
        
        if (isset($current_data[$log_id])) {
            $current_data[$log_id]['lat'] = $lat;
            $current_data[$log_id]['lon'] = $lon;
            
            file_put_contents($file, json_encode($current_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }
}