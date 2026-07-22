<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requireLogin();


$logs = loadLogs();


$points = [];


foreach ($logs as $log) {


    if (
        !empty($log['lat']) &&
        !empty($log['lon'])
    ) {


        $points[] = [

            'ip' => $log['ip'] ?? '',

            'time' => $log['time'] ?? '',

            'lat' => (float)$log['lat'],

            'lon' => (float)$log['lon']

        ];

    }

}


?>
<!DOCTYPE html>
<html lang="ru">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>GPS Map</title>


<link 
rel="stylesheet"
href="https://unpkg.com/leaflet/dist/leaflet.css"
/>


<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>


<style>

body{

margin:0;

background:#070b14;

font-family:Arial,sans-serif;

color:white;

}


.header{

padding:20px;

background:#111827;

}


a{

color:#38bdf8;

text-decoration:none;

}


#map{

height:calc(100vh - 80px);

width:100%;

}


</style>


</head>


<body>


<div class="header">

<h2>
🌍 GPS Monitoring Map
</h2>


<a href="dashboard.php">
← Dashboard
</a>


</div>



<div id="map"></div>




<script>


const points = <?= json_encode(
    $points,
    JSON_UNESCAPED_UNICODE
) ?>;




const map = L.map('map')
.setView(
    [41.3111,69.2797],
    5
);



L.tileLayer(

'https://tile.openstreetmap.org/{z}/{x}/{y}.png',

{

maxZoom:18

}

).addTo(map);





points.forEach(point=>{


let marker=L.marker([

point.lat,

point.lon

])

.addTo(map);



marker.bindPopup(`

<b>IP:</b> ${point.ip}<br>

<b>Time:</b> ${point.time}<br>

<b>GPS:</b>
${point.lat},
${point.lon}

`);




});



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