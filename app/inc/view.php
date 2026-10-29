<?php

function view($tpl, array $data = [])
{
    $file = __DIR__ . '/../views/' . $tpl . '.php';
    if (!is_file($file)) {
        throw new RuntimeException('Шаблон не найден: ' . $tpl);
    }
    extract($data, EXTR_SKIP);
    ob_start();
    include $file;
    return ob_get_clean();
}

function page($title, $contentHtml, array $opts = [])
{
    header('Content-Type: text/html; charset=utf-8');
    $base = basePath();
    $flash = flashDrain();
    $siteName = cfg('site_name');
    $desc = (string)($opts['desc'] ?? '');
    $themeInit = "try{var t=localStorage.getItem('gen.theme');if(!t){t=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-bs-theme',t==='dark'?'dark':'light');document.documentElement.setAttribute('data-theme',t==='dark'?'dark':'light');}catch(e){}";
    $ver = '20260949';
    echo '<!DOCTYPE html>';
    echo '<html lang="ru" data-bs-theme="light" data-theme="light">';
    echo '<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e(($title ? $title . ' — ' : '') . $siteName) . '</title>';
    echo $desc ? '<meta name="description" content="' . e($desc) . '">' : '';
    echo '<meta name="robots" content="noindex, nofollow">';
    echo '<link rel="icon" type="image/svg+xml" href="' . $base . '/assets/img/tree-logo.svg?v=' . $ver . '">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Literata:ital,opsz,wght@0,7..72,500;0,7..72,600;0,7..72,700;1,7..72,500&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">';
    echo '<link rel="stylesheet" href="' . $base . '/assets/css/app.css?20260949">';
    if (!empty($opts['css'])) {
        foreach ((array)$opts['css'] as $c) {
            echo '<link rel="stylesheet" href="' . $base . '/' . $c . '?20260949">';
        }
    }
    echo '<script>' . $themeInit . '</script>';
    echo '</head><body class="' . e((string)($opts['body'] ?? '')) . '">';
    echo view('_shell', [
        'user' => currentUser(),
        'flash' => $flash,
        'content' => $contentHtml,
        'siteName' => $siteName,
        'base' => $base,
    ]);
    foreach ((array)($opts['js'] ?? []) as $j) {
        echo '<script src="' . $base . '/' . $j . '?20260949"></script>';
    }
    echo '</body></html>';
}
