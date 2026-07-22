<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/geo_module.php';

requireLogin();


$logs = loadLogs();


$total = count($logs);

$vpn = 0;

$countries = [];

$hours = [];


foreach ($logs as $log) {


    $geo = getIpInfo(
        $log['ip'] ?? ''
    );


    if ($geo['vpn']) {

        $vpn++;

    }


    $country = $geo['country'];


    if (!isset($countries[$country])) {

        $countries[$country] = 0;

    }


    $countries[$country]++;



    if (!empty($log['time'])) {


        $hour = date(
            'H',
            strtotime($log['time'])
        );


        if (!isset($hours[$hour])) {

            $hours[$hour] = 0;

        }


        $hours[$hour]++;

    }

}


arsort($countries);


?>
<!DOCTYPE html>
<html lang="ru">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard</title>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<style>

body{

background:#070b14;

color:white;

font-family:Arial,sans-serif;

padding:30px;

}


h1{

color:#38bdf8;

}



.grid{

display:grid;

grid-template-columns:

repeat(auto-fit,minmax(220px,1fr));

gap:20px;

}



.card{

background:#111827;

border:1px solid #1f2937;

border-radius:20px;

padding:25px;

}



.number{

font-size:35px;

font-weight:bold;

margin-top:10px;

}



.chart{

margin-top:30px;

background:#111827;

padding:25px;

border-radius:20px;

}



a{

color:#38bdf8;

text-decoration:none;

}


</style>


</head>


<body>


<h1>
📊 Network Monitor Dashboard
</h1>


<br>


<a href="log-view.php">
← Вернуться к журналу
</a>


<br><br>



<div class="grid">


<div class="card">

Всего подключений

<div class="number">

<?= $total ?>

</div>

</div>



<div class="card">

VPN / Hosting

<div class="number" style="color:#f87171">

<?= $vpn ?>

</div>

</div>



<div class="card">

Страны

<div class="number" style="color:#38bdf8">

<?= count($countries) ?>

</div>

</div>



</div>





<div class="chart">

<h3>
Активность по часам
</h3>


<canvas id="hoursChart"></canvas>


</div>





<div class="chart">

<h3>
Топ стран
</h3>


<canvas id="countryChart"></canvas>


</div>





<script>


new Chart(

document.getElementById('hoursChart'),

{


type:'line',


data:{


labels:

<?= json_encode(array_keys($hours)) ?>,


datasets:[{

label:'Подключения',

data:

<?= json_encode(array_values($hours)) ?>,


borderColor:'#38bdf8',

backgroundColor:'rgba(56,189,248,.2)',

fill:true

}]

}


}

);





new Chart(

document.getElementById('countryChart'),

{


type:'doughnut',


data:{


labels:

<?= json_encode(array_keys(array_slice($countries,0,10))) ?>,


datasets:[{

data:

<?= json_encode(array_values(array_slice($countries,0,10))) ?>,


backgroundColor:[

'#38bdf8',
'#34d399',
'#f87171',
'#facc15',
'#a78bfa',
'#fb7185'

]


}]


}


}

);


</script>
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


</body>

</html>