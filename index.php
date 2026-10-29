<?php

require __DIR__ . '/app/bootstrap.php';

$GLOBALS['__base'] = basePath();

$routes = [
    'home' => ['fn' => 'pg_home', 'anon' => true],
    'privacy' => ['fn' => 'pg_privacy', 'anon' => true],
    'login' => ['fn' => 'pg_login', 'anon' => true],
    'forgot' => ['fn' => 'pg_forgot', 'anon' => true],
    'reset' => ['fn' => 'pg_reset', 'anon' => true],
    'accept' => ['fn' => 'pg_accept', 'anon' => true],
    'logout' => ['fn' => 'pg_logout', 'anon' => true],
    'trees' => ['fn' => 'pg_trees'],
    'tree' => ['fn' => 'pg_tree'],
    'tree_settings' => ['fn' => 'pg_tree_settings'],
    'export' => ['fn' => 'pg_export'],
    'person' => ['fn' => 'pg_person'],
    'person_new' => ['fn' => 'pg_person_new'],
    'person_edit' => ['fn' => 'pg_person_edit'],
    'media' => ['fn' => 'pg_media'],
    'media_add' => ['fn' => 'pg_media_add'],
    'media_edit' => ['fn' => 'pg_media_edit'],
    'archive' => ['fn' => 'pg_archive'],
    'profile' => ['fn' => 'pg_profile'],
    'consent' => ['fn' => 'pg_consent'],
    'admin_users' => ['fn' => 'pg_admin_users'],
    'admin_user' => ['fn' => 'pg_admin_user'],
    'api_tree' => ['fn' => 'pg_api_tree'],
    'api_media_persons' => ['fn' => 'pg_api_media_persons'],
    'api_rel_add' => ['fn' => 'pg_api_rel_add'],
    'api_set_parent' => ['fn' => 'pg_api_set_parent'],
    'avatar_upload' => ['fn' => 'pg_avatar_upload'],
    'avatar_save' => ['fn' => 'pg_avatar_save'],
];

require_once __DIR__ . '/app/pages_auth.php';
require_once __DIR__ . '/app/pages_account.php';
require_once __DIR__ . '/app/pages_trees.php';
require_once __DIR__ . '/app/pages_person.php';
require_once __DIR__ . '/app/pages_media.php';
require_once __DIR__ . '/app/pages_admin.php';
require_once __DIR__ . '/app/pages_api.php';

$p = isset($_GET['p']) ? trim((string)$_GET['p']) : '';
if ($p === '') {
    $p = isLoggedIn() ? 'trees' : 'home';
}
if (!isset($routes[$p])) {
    http_response_code(404);
    exit('Страница не найдена.');
}
$route = $routes[$p];
$fn = $route['fn'];

if (isPost()) {
    checkCsrf();
}

if (function_exists($fn)) {
    $fn();
    exit;
}

http_response_code(500);
exit('Ошибка обработки страницы.');
