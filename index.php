<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';

sendSecurityHeaders(false);
ensureCurrentLog(['page' => 'public_demo']);
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#07101d">
  <title>Интерактивная демонстрация</title>
  <link rel="stylesheet" href="<?= h(appUrl('assets/app.css')) ?>">
  <style>
    .demo-page { min-height:100vh; display:grid; place-items:center; padding:24px; overflow:hidden; position:relative; }
    .demo-page::before { content:""; position:fixed; inset:auto auto 8% -8%; width:420px; height:420px; border-radius:50%; background:rgba(34,211,238,.13); filter:blur(80px); }
    .demo { width:min(720px,100%); padding:clamp(22px,5vw,42px); text-align:center; position:relative; }
    .demo-icon { width:88px; height:88px; margin:0 auto 22px; display:grid; place-items:center; border-radius:28px; background:linear-gradient(145deg,#3b82f6,#22d3ee); color:#03111d; font-size:42px; box-shadow:0 24px 60px rgba(34,211,238,.22); animation:float 2.4s ease-in-out infinite alternate; }
    .demo h1 { font-size:clamp(32px,7vw,58px); }
    .demo-copy { max-width:590px; margin:0 auto 26px; color:var(--muted); line-height:1.7; }
    .demo-actions { display:flex; flex-wrap:wrap; justify-content:center; gap:11px; }
    .privacy { margin:26px auto 0; padding:15px 17px; max-width:590px; border:1px solid var(--line); border-radius:15px; background:rgba(5,14,26,.5); color:var(--muted); font-size:13px; line-height:1.6; text-align:left; }
    .privacy strong { color:var(--text); }
    .status { min-height:24px; margin-top:16px; color:#a7f3d0; font-size:14px; }
    .visual { display:none; margin:28px auto 0; width:190px; height:190px; border-radius:48px; background:linear-gradient(145deg,#22d3ee,#3b82f6 52%,#a855f7); box-shadow:0 0 70px rgba(34,211,238,.25); animation:dance .45s ease-in-out infinite alternate; }
    .visual.active { display:block; }
    @keyframes float { to { transform:translateY(-9px) rotate(3deg); } }
    @keyframes dance { from { transform:rotate(-6deg) scale(.96); border-radius:48px; } to { transform:rotate(6deg) scale(1.05); border-radius:50%; } }
    @media(max-width:560px){ .demo-actions .btn{width:100%;} }
  </style>
</head>
<body>
<main class="demo-page">
  <section class="card demo">
    <div class="demo-icon" aria-hidden="true">♪</div>
    <div class="eyebrow">Интерактивная страница</div>
    <h1>Музыкальная пауза</h1>
    <p class="demo-copy">Запустите небольшую анимацию со звуком. Геопозиция не запрашивается автоматически и может быть отправлена только отдельной кнопкой с вашего явного разрешения.</p>

    <div class="demo-actions">
      <button class="btn primary" id="playButton" type="button">▶ Запустить анимацию</button>
      <button class="btn" id="locationButton" type="button">⌖ Поделиться геопозицией</button>
    </div>

    <div class="visual" id="visual" aria-hidden="true"></div>
    <div class="status" id="status" role="status" aria-live="polite"></div>

    <div class="privacy">
      <strong>О данных:</strong> сервер сохраняет стандартные технические сведения запроса, например время, IP и тип браузера. Точные GPS‑координаты сохраняются только после нажатия кнопки выше и разрешения браузера.
    </div>
  </section>
</main>
<script>
const playButton = document.getElementById('playButton');
const locationButton = document.getElementById('locationButton');
const visual = document.getElementById('visual');
const statusBox = document.getElementById('status');
let audioContext;
let animationStarted = false;

function playTone(frequency, start, duration) {
  const oscillator = audioContext.createOscillator();
  const gain = audioContext.createGain();
  oscillator.type = 'sine';
  oscillator.frequency.value = frequency;
  gain.gain.setValueAtTime(0.0001, start);
  gain.gain.exponentialRampToValueAtTime(0.16, start + 0.02);
  gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
  oscillator.connect(gain).connect(audioContext.destination);
  oscillator.start(start);
  oscillator.stop(start + duration + 0.03);
}

playButton.addEventListener('click', async () => {
  audioContext = audioContext || new (window.AudioContext || window.webkitAudioContext)();
  await audioContext.resume();
  const now = audioContext.currentTime;
  [261.63, 329.63, 392, 523.25, 392, 329.63].forEach((note, index) => playTone(note, now + index * 0.18, 0.16));
  animationStarted = !animationStarted;
  visual.classList.toggle('active', animationStarted);
  playButton.textContent = animationStarted ? '■ Остановить анимацию' : '▶ Запустить анимацию';
  statusBox.textContent = animationStarted ? 'Анимация запущена.' : 'Анимация остановлена.';
});

locationButton.addEventListener('click', () => {
  if (!navigator.geolocation) {
    statusBox.textContent = 'Этот браузер не поддерживает геолокацию.';
    return;
  }

  locationButton.disabled = true;
  statusBox.textContent = 'Ожидаем разрешение браузера…';

  navigator.geolocation.getCurrentPosition(async (position) => {
    const body = new FormData();
    body.append('lat', position.coords.latitude);
    body.append('lon', position.coords.longitude);
    body.append('csrf_token', <?= json_encode(csrfToken(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);

    try {
      const response = await fetch(<?= json_encode(appUrl('save.php'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, {
        method: 'POST',
        body,
        credentials: 'same-origin',
        headers: { 'X-CSRF-Token': <?= json_encode(csrfToken(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> }
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Ошибка сохранения');
      statusBox.textContent = data.message;
      locationButton.textContent = '✓ Геопозиция отправлена';
    } catch (error) {
      statusBox.textContent = error.message || 'Не удалось отправить геопозицию.';
      locationButton.disabled = false;
    }
  }, (error) => {
    const messages = {
      1: 'Доступ к геопозиции отклонён.',
      2: 'Геопозиция сейчас недоступна.',
      3: 'Истекло время ожидания геопозиции.'
    };
    statusBox.textContent = messages[error.code] || 'Не удалось получить геопозицию.';
    locationButton.disabled = false;
  }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
});
</script>
</body>
</html>
