<?php
declare(strict_types=1);


require_once __DIR__ . '/config.php';


/*
|--------------------------------------------------------------------------
| Тестовое добавление логов
|--------------------------------------------------------------------------
*/


$logs = loadLogs();


$id = count($logs);



$testIPs = [

    '8.8.8.8',

    '1.1.1.1',

    '185.10.10.10',

    '91.198.174.192'

];



$ip = $testIPs[
    array_rand($testIPs)
];



$logs[$id] = [


    'id'=>$id,


    'time'=>date(
        'Y-m-d H:i:s'
    ),


    'ip'=>$ip,


    'user_agent'=>
    'Mozilla/5.0 Chrome Test',


    'protocol'=>
    'HTTPS',


    'port'=>
    rand(
        40000,
        60000
    ),


    'lat'=>
    '41.3111',


    'lon'=>
    '69.2797'


];



saveLogs($logs);



echo "

<h2 style='font-family:Arial'>

Тестовый лог добавлен

</h2>


<p>

IP:

{$ip}

</p>


<a href='log-view.php'>

Открыть монитор

</a>

";
