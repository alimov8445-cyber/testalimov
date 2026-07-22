<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requireLogin();


/*
|--------------------------------------------------------------------------
| Безопасная функция GEO
|--------------------------------------------------------------------------
*/

if (!function_exists('getIpInfo')) {

    function getIpInfo(string $ip): array
    {
        return [
            'country' => 'Unknown',
            'city' => 'Unknown',
            'isp' => 'Unknown',
            'vpn' => false
        ];
    }

}


/*
|--------------------------------------------------------------------------
| AJAX обновление
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['ajax']) &&
    $_GET['ajax'] === 'count'
) {

    header('Content-Type: application/json');

    echo json_encode([
        'count' => count(loadLogs())
    ]);

    exit;
}



/*
|--------------------------------------------------------------------------
| Экспорт CSV
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['action']) &&
    $_GET['action'] === 'export'
) {

    $logs = loadLogs();


    header(
        'Content-Type: text/csv; charset=utf-8'
    );


    header(
        'Content-Disposition: attachment; filename=network_logs.csv'
    );


    $out = fopen(
        'php://output',
        'w'
    );


    fprintf(
        $out,
        chr(0xEF).chr(0xBB).chr(0xBF)
    );


    fputcsv($out,[

        'ID',
        'TIME',
        'IP',
        'COUNTRY',
        'CITY',
        'ISP',
        'VPN',
        'LAT',
        'LON'

    ]);



    foreach($logs as $id=>$log){


        $geo=getIpInfo(
            $log['ip'] ?? ''
        );


        fputcsv($out,[

            $id,

            $log['time'] ?? '',

            $log['ip'] ?? '',

            $geo['country'],

            $geo['city'],

            $geo['isp'],

            $geo['vpn']
                ? 'YES'
                : 'NO',

            $log['lat'] ?? '',

            $log['lon'] ?? ''

        ]);

    }


    fclose($out);

    exit;

}



/*
|--------------------------------------------------------------------------
| Очистка логов
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['action']) &&
    $_GET['action'] === 'clear'
) {

    saveLogs([]);

    header(
        'Location: log-view.php'
    );

    exit;

}



$logs = array_reverse(
    loadLogs(),
    true
);



$total = count($logs);

$vpnCount = 0;

$countries = [];


foreach($logs as $log){


    $geo = getIpInfo(
        $log['ip'] ?? ''
    );


    if($geo['vpn']){

        $vpnCount++;

    }


    $country =
        $geo['country'] ?? 'Unknown';


    $countries[$country] =
        ($countries[$country] ?? 0)+1;

}

?>
<!DOCTYPE html>
<html lang="ru">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width,initial-scale=1.0">

<title><?= APP_NAME ?></title>

<style>

*{
box-sizing:border-box;
margin:0;
padding:0;
font-family:Arial,sans-serif;
}


body{

background:#070b14;

color:white;

}


.wrapper{

display:flex;

min-height:100vh;

}


.sidebar{

width:250px;

background:#0b1220;

padding:25px;

}


.logo{

font-size:22px;

color:#38bdf8;

font-weight:bold;

margin-bottom:30px;

}


.menu a{

display:block;

padding:12px;

color:#94a3b8;

text-decoration:none;

}


.menu a:hover{

background:#1e293b;

color:white;

}


.main{

flex:1;

padding:30px;

}

.cards{

display:grid;

grid-template-columns:repeat(auto-fit,minmax(220px,1fr));

gap:20px;

margin-bottom:25px;

}


.card{

background:#0b1220;

border:1px solid rgba(255,255,255,.1);

border-radius:18px;

padding:25px;

}


.card h3{

color:#94a3b8;

font-size:14px;

margin-bottom:15px;

}


.number{

font-size:32px;

font-weight:bold;

}


.panel{

background:#0b1220;

border-radius:18px;

padding:25px;

}


.panel-header{

display:flex;

justify-content:space-between;

align-items:center;

margin-bottom:20px;

}


.search{

background:#111827;

border:1px solid #334155;

color:white;

padding:12px;

border-radius:10px;

}


table{

width:100%;

border-collapse:collapse;

}


th{

text-align:left;

padding:12px;

color:#64748b;

}


td{

padding:12px;

border-top:1px solid rgba(255,255,255,.05);

}


.badge{

padding:5px 10px;

border-radius:20px;

font-size:12px;

}


.green{

background:#064e3b;

color:#34d399;

}


.red{

background:#450a0a;

color:#f87171;

}


.map{

color:#38bdf8;

text-decoration:none;

}


.logout{

color:#f87171;

text-decoration:none;

}


</style>

</head>


<body>


<div class="wrapper">


<aside class="sidebar">


<div class="logo">

NET MONITOR

</div>


<div class="menu">

<a href="log-view.php">

📊 Логи

</a>


<a href="?action=export">

⬇ CSV

</a>


<a href="?action=clear"
onclick="return confirm('Очистить логи?')">

🗑 Очистить

</a>


<a href="?logout=1">

🚪 Выход

</a>

</div>


</aside>



<main class="main">



<div style="display:flex;justify-content:space-between;margin-bottom:25px">


<h1>

Система мониторинга

</h1>


<a class="logout" href="?logout=1">

Выйти

</a>


</div>





<div class="cards">


<div class="card">

<h3>

ВСЕГО СОЕДИНЕНИЙ

</h3>

<div class="number">

<?= $total ?>

</div>

</div>




<div class="card">

<h3>

VPN / HOSTING

</h3>

<div class="number" style="color:#f87171">

<?= $vpnCount ?>

</div>

</div>




<div class="card">

<h3>

СТРАНЫ

</h3>

<div class="number" style="color:#38bdf8">

<?= count($countries) ?>

</div>

</div>




<div class="card">

<h3>

STATUS

</h3>

<div class="number" style="color:#34d399">

ONLINE

</div>

</div>


</div>





<div class="panel">


<div class="panel-header">

<h2>

Журнал подключений

</h2>


<input

id="search"

class="search"

placeholder="Поиск..."

>

</div>





<table id="logsTable">


<thead>

<tr>

<th>Время</th>

<th>IP</th>

<th>Страна</th>

<th>Провайдер</th>

<th>VPN</th>

<th>GPS</th>

</tr>

</thead>



<tbody>



<?php foreach($logs as $log): ?>


<?php

$geo=getIpInfo(
    $log['ip'] ?? ''
);

?>


<tr>


<td>

<?=htmlspecialchars($log['time'] ?? '')?>

</td>



<td>

<?=htmlspecialchars($log['ip'] ?? '')?>

</td>



<td>

<?=htmlspecialchars($geo['country'])?>

<br>

<?=htmlspecialchars($geo['city'])?>

</td>



<td>

<?=htmlspecialchars($geo['isp'])?>

</td>



<td>


<?php if($geo['vpn']): ?>

<span class="badge red">

VPN

</span>

<?php else: ?>

<span class="badge green">

CLEAN

</span>

<?php endif; ?>


</td>



<td>


<?php if(

!empty($log['lat']) &&

!empty($log['lon'])

): ?>


<a class="map"

target="_blank"

href="https://www.google.com/maps?q=<?=$log['lat']?>,<?=$log['lon']?>">

🗺 Карта

</a>


<?php else: ?>

нет

<?php endif; ?>


</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>


</main>


</div>




<script>


const search=document.getElementById('search');


search.addEventListener(
'input',
()=>{


let value=search.value.toLowerCase();


document.querySelectorAll(
'#logsTable tbody tr'
).forEach(row=>{


row.style.display =
row.innerText.toLowerCase()
.includes(value)
?
''
:
'none';


});


});



let oldCount=<?=$total?>;



setInterval(()=>{


fetch(
'log-view.php?ajax=count'
)

.then(r=>r.json())

.then(data=>{


if(data.count>oldCount){

location.reload();

}


});


},5000);



</script>


</body>

</html>