<?php

function pg_home()
{
    if (isLoggedIn()) {
        redirect('trees');
    }
    page('', view('landing', []), ['body' => 'landing-page']);
}

function pg_privacy()
{
    page('Политика конфиденциальности', view('privacy', []), ['body' => 'plain-page']);
}

function pg_login()
{
    if (isLoggedIn()) {
        redirect('trees');
    }
    if (isPost()) {
        $email = mb_strtolower(postStr('email'));
        $pwd = (string)($_POST['password'] ?? '');
        $user = null;
        if ($email !== '') {
            $st = db()->prepare('SELECT * FROM users WHERE email = ?');
            $st->execute([$email]);
            $user = $st->fetch();
        }
        if (!$user || !$user['password_hash'] || !password_verify($pwd, $user['password_hash'])) {
            flashSet('danger', 'Неверный e-mail или пароль.');
            redirect('login');
        }
        if ($user['status'] !== 'active') {
            flashSet('warning', 'Учётная запись ещё не активирована. Перейдите по ссылке из письма-приглашения или обратитесь к администратору.');
            redirect('login');
        }
        loginUserById($user['id']);
        flashSet('success', 'Добро пожаловать, ' . ($user['name'] ?: $user['email']) . '!');
        redirect('trees');
    }
    page('Вход', view('auth_login', []), ['body' => 'auth-page']);
}

function pg_logout()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    redirect('login');
}

function pg_forgot()
{
    if (isLoggedIn()) {
        redirect('trees');
    }
    if (isPost()) {
        $email = mb_strtolower(postStr('email'));
        if ($email !== '') {
            $st = db()->prepare('SELECT * FROM users WHERE email = ?');
            $st->execute([$email]);
            $user = $st->fetch();
            if ($user && $user['status'] === 'active') {
                $token = bin2hex(random_bytes(32));
                $exp = date('Y-m-d H:i:s', time() + (int)cfg('reset_hours') * 3600);
                db()->prepare('UPDATE users SET reset_token=?, reset_expires=? WHERE id=?')->execute([$token, $exp, (int)$user['id']]);
                $user['reset_token'] = $token;
                sendResetMail($user);
            }
        }
        flashSet('success', 'Если указанный e-mail зарегистрирован и активен, на него отправлена ссылка для восстановления пароля.');
        redirect('forgot');
    }
    page('Восстановление пароля', view('auth_forgot', []), ['body' => 'auth-page']);
}

function userByTokenColumn($column, $token)
{
    $st = db()->prepare("SELECT * FROM users WHERE {$column} = ?");
    $st->execute([$token]);
    return $st->fetch();
}

function pg_reset()
{
    if (isLoggedIn()) {
        redirect('trees');
    }
    $token = getStr('t');
    $user = $token !== '' ? userByTokenColumn('reset_token', $token) : false;
    if (!$user || $user['status'] !== 'active' || strtotime($user['reset_expires']) < time()) {
        page('Восстановление пароля', view('auth_bad_token', ['title' => 'Ссылка недействительна', 'text' => 'Ссылка для восстановления пароля недействительна или истекла. Запросите восстановление заново.']), ['body' => 'auth-page']);
        return;
    }
    if (isPost()) {
        $pwd = (string)($_POST['password'] ?? '');
        $pwd2 = (string)($_POST['password2'] ?? '');
        $err = validatePassword($pwd);
        if ($err === null && $pwd !== $pwd2) {
            $err = 'Пароли не совпадают.';
        }
        if ($err !== null) {
            flashSet('danger', $err);
            redirect('reset&t=' . rawurlencode($token));
        }
        db()->prepare('UPDATE users SET password_hash=?, reset_token=NULL, reset_expires=NULL WHERE id=?')
            ->execute([password_hash($pwd, PASSWORD_DEFAULT), (int)$user['id']]);
        flashSet('success', 'Пароль изменён. Теперь вы можете войти.');
        redirect('login');
    }
    page('Новый пароль', view('auth_newpwd', ['action' => 'reset', 'token' => $token, 'heading' => 'Новый пароль']), ['body' => 'auth-page']);
}

function pg_accept()
{
    $token = getStr('t');
    $user = $token !== '' ? userByTokenColumn('activation_token', $token) : false;
    if (!$user || $user['status'] !== 'pending' || strtotime($user['activation_expires']) < time()) {
        page('Активация', view('auth_bad_token', ['title' => 'Приглашение недействительно', 'text' => 'Ссылка для активации недействительна или истекла. Попросите администратора отправить новое приглашение.']), ['body' => 'auth-page']);
        return;
    }
    if (isPost()) {
        $pwd = (string)($_POST['password'] ?? '');
        $pwd2 = (string)($_POST['password2'] ?? '');
        $err = validatePassword($pwd);
        if ($err === null && $pwd !== $pwd2) {
            $err = 'Пароли не совпадают.';
        }
        if ($err !== null) {
            flashSet('danger', $err);
            redirect('accept&t=' . rawurlencode($token));
        }
        db()->prepare("UPDATE users SET password_hash=?, status='active', activation_token=NULL, activation_expires=NULL WHERE id=?")
            ->execute([password_hash($pwd, PASSWORD_DEFAULT), (int)$user['id']]);
        loginUserById($user['id']);
        flashSet('success', 'Учётная запись активирована. Добро пожаловать!');
        redirect('trees');
    }
    page('Активация учётной записи', view('auth_accept', ['user' => $user]), ['body' => 'auth-page']);
}
