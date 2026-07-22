<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function isLogged(): bool
{
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
}

function requireLogin(): void
{
    if (!isLogged()) {
        $_SESSION['after_login'] = basename((string)($_SERVER['REQUEST_URI'] ?? 'log-view.php'));
        redirectTo('auth.php');
    }
}

function adminPasswordMatches(string $password): bool
{
    if (ADMIN_PASSWORD_HASH !== '') {
        return password_verify($password, ADMIN_PASSWORD_HASH);
    }
    return hash_equals(ADMIN_PASSWORD, $password);
}

$isAuthEndpoint = realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__;
if (!$isAuthEndpoint) {
    return;
}

sendSecurityHeaders(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    if (!validCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Сессия устарела.');
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
    redirectTo('auth.php');
}

if (isLogged()) {
    redirectTo('log-view.php');
}

$error = '';
$lockedUntil = (int)($_SESSION['login_locked_until'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Сессия устарела. Обновите страницу.';
    } elseif ($lockedUntil > time()) {
        $error = 'Слишком много попыток. Повторите вход через ' . ($lockedUntil - time()) . ' сек.';
    } else {
        $login = trim((string)($_POST['login'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (hash_equals(ADMIN_LOGIN, $login) && adminPasswordMatches($password)) {
            session_regenerate_id(true);
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['login_attempts'] = 0;
            unset($_SESSION['login_locked_until']);
            redirectTo('log-view.php');
        }

        $attempts = (int)($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['login_attempts'] = $attempts;
        if ($attempts >= 5) {
            $_SESSION['login_locked_until'] = time() + 60;
            $_SESSION['login_attempts'] = 0;
        }
        usleep(250000);
        $error = 'Неверный логин или пароль.';
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Вход — <?= h(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= h(appUrl('assets/app.css')) ?>">
</head>
<body>
  <main class="login-page">
    <section class="card login-card">
      <div class="brand">
        <div class="brand-mark">N</div>
        <div><strong><?= h(APP_NAME) ?></strong><small>Административная панель</small></div>
      </div>

      <h1 style="font-size:30px">Добро пожаловать</h1>
      <p class="subtitle" style="margin-bottom:22px">Введите данные администратора для доступа к журналу и аналитике.</p>

      <?php if ($error !== ''): ?>
        <div class="alert error" role="alert"><?= h($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= h(appUrl('auth.php')) ?>" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <div class="form-group">
          <label class="label" for="login">Логин</label>
          <input class="input" id="login" name="login" autocomplete="username" required autofocus>
        </div>
        <div class="form-group">
          <label class="label" for="password">Пароль</label>
          <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn primary" type="submit" style="width:100%; margin-top:4px">Войти в панель</button>
      </form>

      <div class="footer-note">Версия <?= h(APP_VERSION) ?> · защищённая сессия</div>
    </section>
  </main>
</body>
</html>
