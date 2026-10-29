<?php

function pg_admin_users()
{
    $user = requireRole(['admin']);
    if (isPost()) {
        try {
            $new = userCreatePending(postStr('email'), postStr('name'), postStr('role'));
            sendInviteMail($new);
            $link = inviteLinkFor($new['activation_token']);
            flashSet('success', 'Учётная запись создана, приглашение отправлено на ' . $new['email'] . '. Если письмо не дошло — скопируйте ссылку-приглашение в карточке пользователя.');
        } catch (RuntimeException $ex) {
            flashSet('danger', $ex->getMessage());
        }
        redirect('admin_users');
    }
    $users = listUsers();
    page('Пользователи', view('admin_users_view', [
        'users' => $users,
    ]), ['body' => 'plain-page']);
}

function pg_admin_user()
{
    $user = requireRole(['admin']);
    $id = (int)getStr('id');
    $st = db()->prepare('SELECT * FROM users WHERE id=?');
    $st->execute([$id]);
    $target = $st->fetch();
    if (!$target) {
        exit('Пользователь не найден.');
    }
    if (isPost()) {
        $act = postStr('act');
        try {
            if ($act === 'update') {
                adminUpdateUser($id, postStr('name'), postStr('role'), postStr('status'));
                flashSet('success', 'Данные пользователя обновлены.');
            }
            if ($act === 'resend') {
                $u = userResendInvite($id);
                sendInviteMail($u);
                flashSet('success', 'Новое приглашение отправлено.');
            }
            if ($act === 'delete') {
                if (postStr('confirm_text') !== 'УДАЛИТЬ') {
                    flashSet('danger', 'Введите «УДАЛИТЬ» для подтверждения.');
                } else {
                    adminDeleteUser($id);
                    flashSet('success', 'Пользователь удалён вместе с его деревьями.');
                    redirect('admin_users');
                }
            }
        } catch (RuntimeException $ex) {
            flashSet('danger', $ex->getMessage());
        }
        redirect('admin_user&id=' . $id);
    }
    $st = db()->prepare('SELECT * FROM users WHERE id=?');
    $st->execute([$id]);
    $target = $st->fetch();
    $trees = db()->prepare('SELECT * FROM trees WHERE owner_id=? ORDER BY updated_at DESC');
    $trees->execute([$id]);
    $emailMode = cfg('email_mode');
    $inviteLink = $target['status'] === 'pending' && $target['activation_token'] ? inviteLinkFor($target['activation_token']) : '';
    page('Пользователь: ' . ($target['name'] ?: $target['email']), view('admin_user_view', [
        'target' => $target,
        'trees' => $trees->fetchAll(),
        'me' => $user,
        'inviteLink' => $inviteLink,
        'emailMode' => $emailMode,
    ]), ['body' => 'plain-page']);
}
