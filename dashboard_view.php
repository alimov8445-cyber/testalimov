<?php
require_once 'geo_module.php';

// Пример массива данных для отображения в таблице
$sessions = [
    [
        'id' => 101,
        'user' => 'Сотрудник 1',
        'ip' => '8.8.8.8',
        'time' => '2026-07-22 14:30'
    ]
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель управления</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f3f4f6; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        
        /* Таблица */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-size: 13px; color: #6b7280; }
        .badge { background: #e0e7ff; color: #3730a3; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-family: monospace; }

        /* Command Palette UI */
        .cmd-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); justify-content: center; align-items: flex-start; padding-top: 80px; }
        .cmd-box { background: #fff; width: 450px; border-radius: 8px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .cmd-input { width: 100%; padding: 14px; border: none; border-bottom: 1px solid #eee; outline: none; box-sizing: border-box; }
        .cmd-list { list-style: none; margin: 0; padding: 0; }
        .cmd-list li { padding: 10px 15px; cursor: pointer; }
        .cmd-list li:hover { background: #f3f4f6; }
    </style>
</head>
<body>

<div class="container">
    <p>💡 Нажмите <strong>Ctrl + K</strong> для вызова быстрого меню.</p>

    <!-- Блок 1: График (Chart.js) -->
    <div class="card">
        <h3>Аналитика активности</h3>
        <canvas id="mainChart" height="100"></canvas>
    </div>

    <!-- Блок 2: Таблица сессий с IP-геолокацией -->
    <div class="card">
        <h3>Журнал подключений</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th>IP-адрес</th>
                    <th>Город / Страна</th>
                    <th>Примерные координаты</th>
                    <th>Провайдер</th>
                    <th>Время</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $s): ?>
                    <?php $geo = getApproximateLocation($s['ip']); ?>
                    <tr>
                        <td><?= htmlspecialchars($s['id']) ?></td>
                        <td><?= htmlspecialchars($s['user']) ?></td>
                        <td><code><?= htmlspecialchars($s['ip']) ?></code></td>
                        <td><?= htmlspecialchars($geo['city']) ?>, <?= htmlspecialchars($geo['country']) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($geo['coords']) ?></span></td>
                        <td><?= htmlspecialchars($geo['isp']) ?></td>
                        <td><?= htmlspecialchars($s['time']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Модальное окно поиска (Command Palette) -->
<div id="cmdOverlay" class="cmd-overlay">
    <div class="cmd-box">
        <input type="text" id="cmdInput" class="cmd-input" placeholder="Введите команду или раздел..." />
        <ul class="cmd-list">
            <li onclick="alert('Переход в отчеты')">📊 Открыть отчеты</li>
            <li onclick="alert('Настройки систем')">⚙️ Настройки</li>
        </ul>
    </div>
</div>

<script>
    // Инициализация графика
    const ctx = document.getElementById('mainChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт'],
            datasets: [{
                label: 'Запросы',
                data: [50, 100, 75, 120, 90],
                borderColor: '#4f46e5',
                tension: 0.2
            }]
        }
    });

    // Обработка Ctrl+K / Cmd+K
    const overlay = document.getElementById('cmdOverlay');
    const input = document.getElementById('cmdInput');

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            overlay.style.display = 'flex';
            input.focus();
        }
        if (e.key === 'Escape') overlay.style.display = 'none';
    });

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.style.display = 'none';
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