<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';


/*
|--------------------------------------------------------------------------
| Создание записи подключения
|--------------------------------------------------------------------------
*/


function addLog(array $data = []): int
{

    $logs = loadLogs();


    $id = count($logs);



    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Не определен';


    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';


    $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown';


    $port = $_SERVER['REMOTE_PORT'] ?? '';



    $logs[$id] = [


        'id' => $id,


        'time' => date(
            'Y-m-d H:i:s'
        ),


        'ip' => $ip,


        'user_agent' => $agent,


        'protocol' => $protocol,


        'port' => $port,


        'lat' => 'Доступ отклонен или ожидается',


        'lon' => 'Доступ отклонен или ожидается',



        'country' => '',

        'city' => '',

        'isp' => '',

        'vpn' => false,



        'extra'=>$data


    ];



    saveLogs($logs);



    /*
    |--------------------------------------------------------------------------
    | Запоминаем ID для GPS save.php
    |--------------------------------------------------------------------------
    */


    $_SESSION['current_log_id']=$id;



    return $id;

}
