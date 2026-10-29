<?php

function currentUser()
{
    if (isset($GLOBALS['__user'])) {
        return $GLOBALS['__user'];
    }
    $GLOBALS['__user'] = false;
    if (empty($_SESSION['uid'])) {
        return false;
    }
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([(int)$_SESSION['uid']]);
    $user = $st->fetch();
    if (!$user) {
        unset($_SESSION['uid']);
        return false;
    }
    $GLOBALS['__user'] = $user;
    return $user;
}

function isLoggedIn()
{
    return (bool)currentUser();
}

function rolesList()
{
    return ['admin' => 'Администратор', 'user' => 'Пользователь', 'guest' => 'Гость'];
}

function roleLabel($role)
{
    $roles = rolesList();
    return isset($roles[$role]) ? $roles[$role] : $role;
}

function userAllowed($allowedRoles)
{
    if ($allowedRoles === null) {
        return true;
    }
    $user = currentUser();
    if (!$user) {
        return false;
    }
    if (in_array('*', $allowedRoles)) {
        return true;
    }
    return in_array($user['role'], $allowedRoles, true);
}

function requireAuth()
{
    if (!isLoggedIn()) {
        flashSet('warning', 'Войдите в систему, чтобы продолжить.');
        redirect('login');
    }
    $user = currentUser();
    if ($user['status'] !== 'active') {
        session_destroy();
        flashSet('warning', 'Учётная запись не активирована или заблокирована. Обратитесь к администратору.');
        redirect('login');
    }
    return $user;
}

function requireRole($allowedRoles)
{
    requireAuth();
    $user = currentUser();
    if (!userAllowed($allowedRoles)) {
        http_response_code(403);
        flashSet('danger', 'Недостаточно прав для выполнения действия.');
        redirect('home');
    }
    return $user;
}

function contentConsentRequired()
{
    $user = currentUser();
    if (!$user) {
        return false;
    }
    return in_array($user['role'], ['user', 'admin'], true) && (int)$user['consent'] === 1;
}

function requireContentRights($tree)
{
    $user = requireRole(['user', 'admin']);
    if ((int)$user['consent'] !== 1) {
        flashSet('warning', 'Чтобы размещать сведения, необходимо дать согласие на обработку персональных данных.');
        redirect('profile');
    }
    $owner = (int)$tree['owner_id'] === (int)$user['id'];
    $admin = $user['role'] === 'admin';
    if (!$owner && !$admin) {
        http_response_code(403);
        flashSet('danger', 'У вас нет прав на изменение этого дерева.');
        redirect('trees');
    }
    return $user;
}

function canViewTree($user, $tree)
{
    if (!$user) {
        return false;
    }
    if ((int)$tree['owner_id'] === (int)$user['id'] || $user['role'] === 'admin') {
        return true;
    }
    return (int)$tree['is_public'] === 1 && in_array($user['role'], ['guest', 'user', 'admin'], true);
}

function canEditTree($user, $tree)
{
    if (!$user || $user['role'] === 'guest' || $user['status'] !== 'active') {
        return false;
    }
    if ((int)$user['consent'] !== 1) {
        return false;
    }
    if ($user['role'] === 'admin') {
        return true;
    }
    return (int)$tree['owner_id'] === (int)$user['id'];
}

function loadTree($id)
{
    $st = db()->prepare('SELECT t.*, u.email AS owner_email, u.name AS owner_name
                         FROM trees t JOIN users u ON u.id = t.owner_id WHERE t.id = ?');
    $st->execute([(int)$id]);
    return $st->fetch();
}

function requireTreeView($id)
{
    $user = requireAuth();
    $tree = loadTree($id);
    if (!$tree) {
        http_response_code(404);
        exit('Дерево не найдено.');
    }
    if (!canViewTree($user, $tree)) {
        http_response_code(403);
        exit('Доступ к дереву ограничен.');
    }
    return [$user, $tree];
}

function requireTreeEdit($id)
{
    $user = requireAuth();
    $tree = loadTree($id);
    if (!$tree) {
        http_response_code(404);
        exit('Дерево не найдено.');
    }
    if (!canEditTree($user, $tree)) {
        flashSet('danger', 'Недостаточно прав для изменения дерева.');
        redirect('tree&id=' . (int)$id);
    }
    return [$user, $tree];
}

function isTreeOwnerOrAdmin($user, $tree)
{
    return $user && ($user['role'] === 'admin' || (int)$user['id'] === (int)$tree['owner_id']);
}

function loginUserById($id)
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$id;
    $st = db()->prepare('UPDATE users SET last_login = ? WHERE id = ?');
    $st->execute([nowSql(), (int)$id]);
    unset($GLOBALS['__user']);
}

function validatePassword($pwd)
{
    if (mb_strlen($pwd) < (int)cfg('pwd_min_len')) {
        return 'Пароль должен содержать не менее ' . (int)cfg('pwd_min_len') . ' символов.';
    }
    if (!preg_match('/[A-Za-zА-Яа-яЁё]/u', $pwd) || !preg_match('/[0-9]/', $pwd)) {
        return 'Пароль должен содержать буквы и цифры.';
    }
    return null;
}
