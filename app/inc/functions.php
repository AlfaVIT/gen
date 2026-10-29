<?php

$GLOBALS['__cfg'] = require __DIR__ . '/../config.php';

function cfg($key, $def = null)
{
    $parts = explode('.', $key);
    $cur = $GLOBALS['__cfg'];
    foreach ($parts as $p) {
        if (!is_array($cur) || !array_key_exists($p, $cur)) {
            return $def;
        }
        $cur = $cur[$p];
    }
    return $cur;
}

function basePath()
{
    if (isset($GLOBALS['__base'])) {
        return $GLOBALS['__base'];
    }
    $self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/index.php';
    $dir = str_replace('\\', '/', dirname($self));
    $dir = trim($dir, '/');
    $base = $dir === '' || $dir === '.' ? '' : '/' . $dir;
    $GLOBALS['__base'] = $base;
    return $base;
}

function u($path = '')
{
    $base = basePath();
    if ($path === '' || $path === null) {
        return $base . '/index.php';
    }
    if (strpos($path, '/') === 0) {
        return $base . $path;
    }
    if (strpos($path, '.') === false && strpos($path, '/') === false && preg_match('/^[A-Za-z0-9_]+(&[A-Za-z0-9_=&.\-]+)?$/', $path)) {
        return $base . '/index.php?p=' . $path;
    }
    return $base . '/' . $path;
}

function urlAbs($path = '')
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    return $scheme . '://' . $host . u($path);
}

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header('Location: ' . u($path));
    exit;
}

function jsonOut($data, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function nowSql()
{
    return date('Y-m-d H:i:s');
}

function startsSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('gensec');
        session_set_cookie_params(['lifetime' => 0, 'path' => basePath() ?: '/', 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

function flashSet($type, $text)
{
    $_SESSION['__flash'][] = ['type' => $type, 'text' => $text];
}

function flashDrain()
{
    $list = isset($_SESSION['__flash']) ? $_SESSION['__flash'] : [];
    unset($_SESSION['__flash']);
    return $list;
}

function csrfToken()
{
    if (empty($_SESSION['__csrf'])) {
        $_SESSION['__csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['__csrf'];
}

function csrfField()
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function checkCsrf()
{
    $token = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
    if ($token === '' || !hash_equals($_SESSION['__csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Неверный CSRF-токен. Обновите страницу и повторите действие.');
    }
}

function postStr($key, $def = '')
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $def;
}

function getStr($key, $def = '')
{
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : $def;
}

function isPost()
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
