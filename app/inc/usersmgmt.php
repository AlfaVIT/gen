<?php

function adminHasAdmin()
{
    $st = db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'");
    return (int)$st->fetchColumn() > 0;
}

function userCreatePending($email, $name, $role)
{
    $roles = ['user', 'guest'];
    if (!in_array($role, $roles, true)) {
        throw new RuntimeException('Недопустимая роль.');
    }
    $email = mb_strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Некорректный e-mail.');
    }
    $st = db()->prepare('SELECT id FROM users WHERE email = ?');
    $st->execute([$email]);
    if ($st->fetch()) {
        throw new RuntimeException('Пользователь с таким e-mail уже существует.');
    }
    $token = bin2hex(random_bytes(32));
    $exp = date('Y-m-d H:i:s', time() + (int)cfg('invite_days') * 86400);
    $st = db()->prepare('INSERT INTO users (email, name, password_hash, role, status, activation_token, activation_expires, consent, created_at)
        VALUES (?,?,NULL,?,?,?,?,0,?)');
    $st->execute([$email, trim($name), $role, 'pending', $token, $exp, nowSql()]);
    return [
        'id' => (int)db()->lastInsertId(),
        'email' => $email,
        'name' => trim($name),
        'role' => $role,
        'activation_token' => $token,
        'activation_expires' => $exp,
    ];
}

function userResendInvite($id)
{
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([(int)$id]);
    $user = $st->fetch();
    if (!$user || $user['status'] !== 'pending') {
        throw new RuntimeException('Активация не требуется.');
    }
    $token = bin2hex(random_bytes(32));
    $exp = date('Y-m-d H:i:s', time() + (int)cfg('invite_days') * 86400);
    db()->prepare('UPDATE users SET activation_token=?, activation_expires=? WHERE id=?')->execute([$token, $exp, (int)$user['id']]);
    $user['activation_token'] = $token;
    $user['activation_expires'] = $exp;
    return $user;
}

function adminUpdateUser($id, $name, $role, $status)
{
    $st = db()->prepare('SELECT * FROM users WHERE id=?');
    $st->execute([(int)$id]);
    $user = $st->fetch();
    if (!$user) {
        throw new RuntimeException('Пользователь не найден.');
    }
    $me = currentUser();
    if ((int)$user['id'] === (int)$me['id'] && ($role !== $user['role'] || $status !== 'active')) {
        throw new RuntimeException('Нельзя изменить собственную роль или заблокировать себя.');
    }
    $allowedRoles = ['admin', 'user', 'guest'];
    if (!in_array($role, $allowedRoles, true)) {
        throw new RuntimeException('Недопустимая роль.');
    }
    if (!in_array($status, ['active', 'pending', 'blocked'], true)) {
        throw new RuntimeException('Недопустимый статус.');
    }
    db()->prepare('UPDATE users SET name=?, role=?, status=? WHERE id=?')->execute([trim($name), $role, $status, (int)$id]);
}

function adminDeleteUser($id)
{
    $st = db()->prepare('SELECT * FROM users WHERE id=?');
    $st->execute([(int)$id]);
    $user = $st->fetch();
    if (!$user) {
        return;
    }
    $me = currentUser();
    if ((int)$user['id'] === (int)$me['id']) {
        throw new RuntimeException('Нельзя удалить собственную учётную запись.');
    }
    deleteAllUserTrees((int)$id);
    db()->prepare('DELETE FROM users WHERE id=?')->execute([(int)$id]);
}

function listUsers()
{
    $rows = db()->query('SELECT u.*,
        (SELECT COUNT(*) FROM trees t WHERE t.owner_id = u.id) AS tree_count
        FROM users u ORDER BY u.created_at DESC')->fetchAll();
    return $rows;
}

function inviteLinkFor($token)
{
    return urlAbs('index.php?p=accept&t=' . rawurlencode($token));
}

function statusLabel($s)
{
    return [
        'active' => 'Активна',
        'pending' => 'Приглашение отправлено',
        'blocked' => 'Заблокирована',
    ][$s] ?? $s;
}

function statusClass($s)
{
    return ['active' => 'success', 'pending' => 'warning', 'blocked' => 'secondary'][$s] ?? 'secondary';
}
