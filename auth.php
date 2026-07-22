<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/*
|--------------------------------------------------------------------------
| Проверка авторизации
|--------------------------------------------------------------------------
*/

function isLogged(): bool
{
    return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}

function requireLogin(): void
{
    if (!isLogged()) {
        header('Location: auth.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Выход
|--------------------------------------------------------------------------
*/

if (isset($_GET['logout'])) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header("Location: auth.php");

    exit;
}

/*
|--------------------------------------------------------------------------
| Вход
|--------------------------------------------------------------------------
*/

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

   if (
    $login === ADMIN_LOGIN &&
    $password === ADMIN_PASSWORD
) {
        session_regenerate_id(true);

        $_SESSION['admin'] = true;

        header('Location: log-view.php');

        exit;
    }

    $error = 'Неверный логин или пароль.';
}

if (isLogged()) {
    header('Location: log-view.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Авторизация</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Inter,sans-serif;
}


body{

height:100vh;

display:flex;

justify-content:center;

align-items:center;

background:#09090b;

overflow:hidden;

}

body::before{

content:"";

position:absolute;

width:700px;

height:700px;

background:linear-gradient(45deg,#2563eb,#06b6d4);

filter:blur(180px);

opacity:.45;

}

.login{

position:relative;

width:420px;

background:rgba(255,255,255,.06);

border:1px solid rgba(255,255,255,.12);

backdrop-filter:blur(18px);

padding:40px;

border-radius:22px;

box-shadow:0 15px 45px rgba(0,0,0,.45);

}

.logo{

font-size:28px;

font-weight:700;

color:#fff;

margin-bottom:10px;

}

.desc{

color:#9ca3af;

margin-bottom:30px;

}

.input{

width:100%;

padding:15px;

margin-bottom:18px;

border:none;

outline:none;

border-radius:12px;

background:#18181b;

color:white;

font-size:15px;

}

.button{

width:100%;

padding:15px;

border:none;

border-radius:12px;

cursor:pointer;

background:linear-gradient(90deg,#2563eb,#06b6d4);

color:white;

font-size:16px;

font-weight:700;

transition:.3s;

}

.button:hover{

transform:translateY(-2px);

box-shadow:0 0 25px rgba(37,99,235,.5);

}

.error{

margin-bottom:18px;

padding:12px;

background:#7f1d1d;

border-radius:10px;

color:white;

}

.footer{

margin-top:25px;

text-align:center;

color:#6b7280;

font-size:13px;

}

</style>

</head>

<body>

<div class="login">

<div class="logo">
Network Log Monitor
</div>

<div class="desc">
Вход в панель администратора
</div>

<?php if($error): ?>

<div class="error">
<?= htmlspecialchars($error) ?>
</div>

<?php endif; ?>

<form method="POST">

<input
class="input"
name="login"
placeholder="Логин"
required>

<input
class="input"
type="password"
name="password"
placeholder="Пароль"
required>

<button class="button">

Войти

</button>

</form>

<div class="footer">

Version <?= APP_VERSION ?>

</div>

</div>

</body>

</html>