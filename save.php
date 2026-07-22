<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';


/*
|--------------------------------------------------------------------------
| Сохранение GPS координат
|--------------------------------------------------------------------------
| Принимает координаты от клиента
| и обновляет соответствующий лог в logs.json
|--------------------------------------------------------------------------
*/


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    exit('Method Not Allowed');

}



if (!isset($_SESSION['current_log_id'])) {

    http_response_code(403);

    exit('Session log ID missing');

}



$logId = $_SESSION['current_log_id'];


$lat = trim($_POST['lat'] ?? '');

$lon = trim($_POST['lon'] ?? '');



if ($lat === '' || $lon === '') {

    http_response_code(400);

    exit('Coordinates missing');

}



/*
|--------------------------------------------------------------------------
| Загружаем логи
|--------------------------------------------------------------------------
*/


$logs = loadLogs();



if (!isset($logs[$logId])) {

    http_response_code(404);

    exit('Log entry not found');

}



/*
|--------------------------------------------------------------------------
| Обновляем координаты
|--------------------------------------------------------------------------
*/


$logs[$logId]['lat'] = $lat;

$logs[$logId]['lon'] = $lon;


$logs[$logId]['gps_time'] = date(
    'Y-m-d H:i:s'
);



/*
|--------------------------------------------------------------------------
| Сохраняем безопасно
|--------------------------------------------------------------------------
*/


if (saveLogs($logs)) {

    echo json_encode([

        'status'=>'success',

        'message'=>'GPS updated'

    ], JSON_UNESCAPED_UNICODE);


} else {


    http_response_code(500);


    echo json_encode([

        'status'=>'error',

        'message'=>'Save failed'

    ]);

}