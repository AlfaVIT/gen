<?php

function mailSend($to, $subject, $bodyHtml)
{
    $mode = cfg('email_mode', 'mail');
    if ($mode === 'log') {
        $line = "[" . nowSql() . "] TO: {$to} SUBJ: {$subject}\n" . $bodyHtml . "\n" . str_repeat('-', 60) . "\n";
        @file_put_contents(cfg('email_log'), $line, FILE_APPEND);
        return true;
    }
    if ($mode === 'smtp') {
        return mailSendSmtp($to, $subject, $bodyHtml);
    }
    return mailSendPhp($to, $subject, $bodyHtml);
}

function mailSendPhp($to, $subject, $bodyHtml)
{
    $from = cfg('app_email');
    $name = cfg('app_email_name');
    $headers = "MIME-Version: 1.0\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . 'From: =?UTF-8?B?' . base64_encode($name) . "?= <{$from}>\r\n"
        . "X-Mailer: PHP/" . phpversion();
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $bodyHtml, $headers);
}

function mailSendSmtp($to, $subject, $bodyHtml)
{
    $s = cfg('smtp');
    $host = $s['host'];
    $port = (int)$s['port'];
    if (!$host) {
        return false;
    }
    $secure = $s['secure'];
    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $fp = @stream_socket_client($remote, $errno, $errstr, 15);
    if (!$fp) {
        return false;
    }
    stream_set_timeout($fp, 15);
    $read = function () use ($fp) {
        $out = '';
        while ($line = fgets($fp, 515)) {
            $out .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $out;
    };
    $send = function ($cmd) use ($fp, $read) {
        fwrite($fp, $cmd . "\r\n");
        return $read();
    };
    $read();
    $send('EHLO ' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'));
    if ($secure === 'tls') {
        $resp = $send('STARTTLS');
        if (strpos($resp, '220') !== 0) {
            fclose($fp);
            return false;
        }
        $crypto = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$crypto) {
            fclose($fp);
            return false;
        }
        $read();
        $send('EHLO ' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'));
    }
    if ($s['user'] !== '') {
        $send('AUTH LOGIN');
        $send(base64_encode($s['user']));
        $resp = $send(base64_encode($s['pass']));
        if (strpos($resp, '235') !== 0) {
            fclose($fp);
            return false;
        }
    }
    $send('MAIL FROM:<' . cfg('app_email') . '>');
    $resp = $send('RCPT TO:<' . $to . '>');
    if (strpos($resp, '250') !== 0 && strpos($resp, '251') !== 0) {
        fclose($fp);
        return false;
    }
    $send('DATA');
    $msg = 'From: =?UTF-8?B?' . base64_encode(cfg('app_email_name')) . '?= <' . cfg('app_email') . ">\r\n"
        . 'To: <' . $to . ">\r\n"
        . 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n"
        . "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n"
        . "\r\n" . $bodyHtml . "\r\n.";
    fwrite($fp, $msg . "\r\n");
    $resp = $read();
    fclose($fp);
    return strpos($resp, '250') === 0;
}

function mailLayout($title, $innerHtml)
{
    $accent = cfg('accent', '#b5814d');
    return '<div style="font-family:Arial,Helvetica,sans-serif;background:#f3f0ea;padding:24px;color:#333">'
        . '<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e4ded2">'
        . '<div style="background:' . $accent . ';color:#fff;padding:18px 26px;font-size:19px;font-weight:700">' . e(cfg('site_name')) . '</div>'
        . '<div style="padding:26px;line-height:1.6">'
        . '<h1 style="font-size:20px;margin:0 0 14px">' . e($title) . '</h1>'
        . $innerHtml
        . '</div></div>'
        . '<p style="text-align:center;font-size:12px;color:#999;margin-top:14px">Если вы не запрашивали это письмо — просто проигнорируйте его.</p>'
        . '</div>';
}

function sendInviteMail($user)
{
    $link = urlAbs('index.php?p=accept&t=' . rawurlencode($user['activation_token']));
    $html = '<p>Здравствуйте, ' . e($user['name']) . '!</p>'
        . '<p>Для вас создана учётная запись на сайте «' . e(cfg('site_name')) . '» с ролью «' . e(roleLabel($user['role'])) . '».</p>'
        . '<p>Чтобы активировать учётную запись и задать пароль, перейдите по ссылке:</p>'
        . '<p><a href="' . $link . '" style="display:inline-block;background:' . cfg('accent') . ';color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none">Активировать учётную запись</a></p>'
        . '<p>Ссылка действительна ' . (int)cfg('invite_days') . ' дней.</p>';
    return mailSend($user['email'], 'Активация учётной записи', mailLayout('Активация учётной записи', $html));
}

function sendResetMail($user)
{
    $link = urlAbs('index.php?p=reset&t=' . rawurlencode($user['reset_token']));
    $html = '<p>Здравствуйте, ' . e($user['name']) . '!</p>'
        . '<p>Вы запросили восстановление пароля на сайте «' . e(cfg('site_name')) . '». Перейдите по ссылке, чтобы задать новый пароль:</p>'
        . '<p><a href="' . $link . '" style="display:inline-block;background:' . cfg('accent') . ';color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none">Восстановить пароль</a></p>'
        . '<p>Ссылка действительна ' . (int)cfg('reset_hours') . ' часа(ов). Если вы не запрашивали восстановление — проигнорируйте письмо.</p>';
    return mailSend($user['email'], 'Восстановление пароля', mailLayout('Восстановление пароля', $html));
}
