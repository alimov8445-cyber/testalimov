<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';


/*
|--------------------------------------------------------------------------
| Модуль геолокации IP
|--------------------------------------------------------------------------
| - Кэширует результаты
| - Не делает повторные запросы
| - Работает с JSON
|--------------------------------------------------------------------------
*/


function getIpInfo(string $ip): array
{

    /*
    |--------------------------------------------------------------------------
    | Локальные адреса
    |--------------------------------------------------------------------------
    */

    if (
        $ip === '127.0.0.1' ||
        $ip === '::1' ||
        filter_var($ip, FILTER_VALIDATE_IP) === false
    ) {

        return [

            'ip' => $ip,

            'country' => 'Local',

            'city' => 'Localhost',

            'isp' => 'Local Network',

            'lat' => '',

            'lon' => '',

            'hosting' => false,

            'vpn' => false,

            'cached' => false

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | Проверка кэша
    |--------------------------------------------------------------------------
    */

    $cacheFile = CACHE_DIR . '/' . md5($ip) . '.json';


    if (file_exists($cacheFile)) {


        $cache = json_decode(
            file_get_contents($cacheFile),
            true
        );


        if (is_array($cache)) {

            $cache['cached'] = true;

            return $cache;

        }

    }



    /*
    |--------------------------------------------------------------------------
    | Запрос IP API
    |--------------------------------------------------------------------------
    */


    $url =
    "https://ip-api.com/json/" .
    urlencode($ip) .
    "?fields=status,country,city,isp,org,lat,lon,hosting";


    $ch = curl_init();


    curl_setopt_array($ch,[

        CURLOPT_URL => $url,

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_TIMEOUT => 5,

        CURLOPT_SSL_VERIFYPEER => true

    ]);


    $response = curl_exec($ch);


    curl_close($ch);



    /*
    |--------------------------------------------------------------------------
    | Ошибка API
    |--------------------------------------------------------------------------
    */


    if (!$response) {


        return [

            'ip'=>$ip,

            'country'=>'Unknown',

            'city'=>'Unknown',

            'isp'=>'Unknown',

            'lat'=>'',

            'lon'=>'',

            'hosting'=>false,

            'vpn'=>false,

            'cached'=>false

        ];

    }



    $data=json_decode(
        $response,
        true
    );



    if (
        !is_array($data) ||
        ($data['status'] ?? '') !== 'success'
    ) {


        return [

            'ip'=>$ip,

            'country'=>'Unknown',

            'city'=>'Unknown',

            'isp'=>'Unknown',

            'lat'=>'',

            'lon'=>'',

            'hosting'=>false,

            'vpn'=>false,

            'cached'=>false

        ];

    }



    /*
    |--------------------------------------------------------------------------
    | Формирование результата
    |--------------------------------------------------------------------------
    */


    $result=[

        'ip'=>$ip,

        'country'=>$data['country'] ?? 'Unknown',

        'city'=>$data['city'] ?? 'Unknown',

        'isp'=>$data['isp'] ?? ($data['org'] ?? 'Unknown'),

        'lat'=>$data['lat'] ?? '',

        'lon'=>$data['lon'] ?? '',


        /*
        hosting = датацентр/VPS
        Это не 100% VPN,
        поэтому сохраняем отдельно
        */

        'hosting'=>
        ($data['hosting'] ?? false) === true,


        'vpn'=>
        ($data['hosting'] ?? false) === true,


        'cached'=>false

    ];



    /*
    |--------------------------------------------------------------------------
    | Сохранение кэша
    |--------------------------------------------------------------------------
    */


    file_put_contents(

        $cacheFile,

        json_encode(
            $result,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_UNICODE
        ),

        LOCK_EX

    );



    return $result;

}



/*
|--------------------------------------------------------------------------
| Совместимость со старым кодом
|--------------------------------------------------------------------------
*/


function getApproximateLocation(string $ip): array
{

    $info=getIpInfo($ip);


    return [

        'city'=>$info['city'],

        'country'=>$info['country'],

        'coords'=>
            $info['lat'] .
            ', ' .
            $info['lon'],

        'isp'=>$info['isp']

    ];

}