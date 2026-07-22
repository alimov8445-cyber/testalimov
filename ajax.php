<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requireLogin();


header('Content-Type: application/json; charset=utf-8');


/*
|--------------------------------------------------------------------------
| AJAX API
|--------------------------------------------------------------------------
*/


$action = $_GET['action'] ?? '';



switch ($action) {


    /*
    |--------------------------------------------------------------------------
    | Количество логов
    |--------------------------------------------------------------------------
    */


    case 'count':


        $logs = loadLogs();


        echo json_encode([

            'count' => count($logs)

        ]);


        break;



    /*
    |--------------------------------------------------------------------------
    | Получение последних логов
    |--------------------------------------------------------------------------
    */


    case 'logs':


        $logs = array_reverse(
            loadLogs(),
            true
        );


        echo json_encode(

            $logs,

            JSON_UNESCAPED_UNICODE

        );


        break;



    /*
    |--------------------------------------------------------------------------
    | Статистика
    |--------------------------------------------------------------------------
    */


    case 'stats':


        $logs = loadLogs();


        $countries = [];

        $ips = [];

        $total = count($logs);



        foreach($logs as $log){


            if(isset($log['ip'])){

                $ips[$log['ip']] = true;

            }


            if(isset($log['country'])){


                $country=$log['country'];


                if(!isset($countries[$country])){

                    $countries[$country]=0;

                }


                $countries[$country]++;


            }


        }



        echo json_encode([

            'total'=>$total,

            'unique_ips'=>count($ips),

            'countries'=>$countries

        ],JSON_UNESCAPED_UNICODE);


        break;



    default:


        echo json_encode([

            'error'=>'Unknown action'

        ]);


}