<<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/geo_module.php';

requireLogin();


/*
|--------------------------------------------------------------------------
| Обработка действий
|--------------------------------------------------------------------------
*/


// Очистка логов

if (
    isset($_GET['action']) &&
    $_GET['action'] === 'clear'
) {


    if (
        isset($_POST['csrf']) &&
        verifyCsrf($_POST['csrf'])
    ) {

        saveLogs([]);

    }


    header(
        "Location: log-view.php"
    );

    exit;

}




// Экспорт CSV

if (
    isset($_GET['action']) &&
    $_GET['action'] === 'export'
) {


    $logs = loadLogs();


    header(
        'Content-Type: text/csv; charset=utf-8'
    );


    header(
        'Content-Disposition: attachment; filename=network_logs_'
        .date('Y-m-d_H-i-s')
        .'.csv'
    );


    $out=fopen(
        'php://output',
        'w'
    );


    fprintf(
        $out,
        chr(0xEF).chr(0xBB).chr(0xBF)
    );


    fputcsv($out,[

        'ID',
        'Time',
        'IP',
        'Country',
        'City',
        'ISP',
        'VPN',
        'Latitude',
        'Longitude'

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



$logs = array_reverse(
    loadLogs(),
    true
);



$total=count($logs);


$vpnCount=0;


$countries=[];


foreach($logs as $log){


    $geo=getIpInfo(
        $log['ip'] ?? ''
    );


    if($geo['vpn']){

        $vpnCount++;

    }


    $country=$geo['country'];


    if(!isset($countries[$country])){

        $countries[$country]=0;

    }


    $countries[$country]++;

}


?>
<!DOCTYPE html>
<html lang="ru">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
<?= APP_NAME ?>
</title>


<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">


<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}


body{

    background:#070b14;

    color:#e5e7eb;

    font-family:Inter,Arial,sans-serif;

    min-height:100vh;

}


/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/


.wrapper{

    display:flex;

    min-height:100vh;

}



.sidebar{

    width:260px;

    background:#0b1220;

    border-right:1px solid rgba(255,255,255,.08);

    padding:25px;

}



.logo{

    font-size:22px;

    font-weight:700;

    color:#38bdf8;

    margin-bottom:40px;

}



.menu a{

    display:block;

    color:#94a3b8;

    text-decoration:none;

    padding:13px;

    border-radius:12px;

    margin-bottom:8px;

    transition:.3s;

}



.menu a:hover{

    background:#1e293b;

    color:white;

}



.main{

    flex:1;

    padding:30px;

}



/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/


.header{

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:30px;

}



.title{

    font-size:28px;

    font-weight:700;

}



.logout{

    color:#f87171;

    text-decoration:none;

}



/*
|--------------------------------------------------------------------------
| Cards
|--------------------------------------------------------------------------
*/


.cards{

    display:grid;

    grid-template-columns:
    repeat(auto-fit,minmax(220px,1fr));

    gap:20px;

    margin-bottom:30px;

}



.card{

    background:
    linear-gradient(
        145deg,
        rgba(255,255,255,.08),
        rgba(255,255,255,.02)
    );


    border:

    1px solid rgba(255,255,255,.08);


    border-radius:20px;

    padding:25px;


    backdrop-filter:blur(15px);

}



.card h3{

    color:#94a3b8;

    font-size:14px;

    margin-bottom:15px;

}



.number{

    font-size:34px;

    font-weight:700;

    color:white;

}



/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/


.panel{

    background:#0b1220;

    border-radius:20px;

    padding:25px;

    border:1px solid rgba(255,255,255,.08);

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

    padding:12px 15px;

    border-radius:12px;

    width:280px;

}



table{

    width:100%;

    border-collapse:collapse;

}



th{

    text-align:left;

    padding:14px;

    color:#64748b;

    font-size:13px;

}



td{

    padding:14px;

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



.blue{

    background:#082f49;

    color:#38bdf8;

}



.map{

    color:#38bdf8;

    text-decoration:none;

}



@media(max-width:900px){

.sidebar{

    display:none;

}


.main{

    padding:15px;

}


.search{

    width:100%;

}

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


<a href="#">
📊 Dashboard
</a>


<a href="?action=export">
⬇ Экспорт CSV
</a>


<a href="?logout=1">
🚪 Выход
</a>


</div>


</aside>



<main class="main">


<div class="header">


<div class="title">

Система мониторинга

</div>


<div>

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
HOSTING / VPN
</h3>

<div class="number" style="color:#f87171">

<?= $vpnCount ?>

</div>

</div>



<div class="card">

<h3>
УНИКАЛЬНЫЕ СТРАНЫ
</h3>

<div class="number" style="color:#38bdf8">

<?= count($countries) ?>

</div>

</div>



<div class="card">

<h3>
СТАТУС СИСТЕМЫ
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

placeholder="Поиск IP, страны, ISP..."

>


</div>





<table id="logsTable">


<thead>


<tr>

<th>
Время
</th>

<th>
IP
</th>

<th>
Местоположение
</th>

<th>
Провайдер
</th>

<th>
VPN
</th>

<th>
GPS
</th>

</tr>


</thead>



<tbody>


<?php foreach($logs as $id=>$log): ?>


<?php

$geo=getIpInfo(
    $log['ip'] ?? ''
);

?>


<tr>


<td>

<?= htmlspecialchars(
    $log['time'] ?? ''
) ?>

</td>



<td>

<strong>

<?= htmlspecialchars(
    $log['ip'] ?? ''
) ?>

</strong>


</td>



<td>


<?= htmlspecialchars(
    $geo['country']
) ?>


<br>


<span style="color:#94a3b8">

<?= htmlspecialchars(
    $geo['city']
) ?>

</span>


</td>




<td>


<?= htmlspecialchars(
    $geo['isp']
) ?>


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


<a

class="map"

target="_blank"

href="https://www.google.com/maps?q=<?= 
$log['lat']
?>,<?= 
$log['lon']
?>"

>

🗺 Карта

</a>


<?php else: ?>


<span style="color:#64748b">

нет

</span>


<?php endif; ?>


</td>



</tr>



<?php endforeach; ?>


</tbody>


</table>


</div> 
<script>


/*
|--------------------------------------------------------------------------
| Поиск по таблице
|--------------------------------------------------------------------------
*/


const search = document.getElementById('search');


search.addEventListener('input', function(){


    let value = this.value.toLowerCase();


    let rows = document.querySelectorAll(
        '#logsTable tbody tr'
    );


    rows.forEach(row=>{


        let text=row.innerText.toLowerCase();


        if(text.includes(value)){


            row.style.display='';


        }else{


            row.style.display='none';


        }


    });


});



/*
|--------------------------------------------------------------------------
| Автообновление страницы
|--------------------------------------------------------------------------
| Проверка новых логов
|--------------------------------------------------------------------------
*/


let lastCount = <?= $total ?>;



setInterval(()=>{


fetch('log-view.php?ajax=count')


.then(r=>r.json())


.then(data=>{


    if(data.count > lastCount){


        location.reload();


    }


});


},5000);
<script>

document.addEventListener(
"DOMContentLoaded",
()=>{

const clock=document.getElementById(
"systemClock"
);


if(clock){

setInterval(()=>{

clock.innerText =
new Date().toLocaleString("ru-RU");

},1000);

}

});

</script>

</body>
</html>



</script>



</main>


</div>


</body>

</html>