<?php

function pg_profile()
{
    $user = requireAuth();
    if (isPost()) {
        $act = postStr('act');
        if ($act === 'update_name') {
            $name = postStr('name');
            db()->prepare('UPDATE users SET name=? WHERE id=?')->execute([$name, (int)$user['id']]);
            flashSet('success', 'Имя обновлено.');
            redirect('profile');
        }
        if ($act === 'change_password') {
            $old = (string)($_POST['old_password'] ?? '');
            $pwd = (string)($_POST['password'] ?? '');
            $pwd2 = (string)($_POST['password2'] ?? '');
            if (!password_verify($old, $user['password_hash'])) {
                flashSet('danger', 'Текущий пароль указан неверно.');
                redirect('profile');
            }
            $err = validatePassword($pwd);
            if ($err === null && $pwd !== $pwd2) {
                $err = 'Пароли не совпадают.';
            }
            if ($err !== null) {
                flashSet('danger', $err);
                redirect('profile');
            }
            db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pwd, PASSWORD_DEFAULT), (int)$user['id']]);
            flashSet('success', 'Пароль успешно изменён.');
            redirect('profile');
        }
        redirect('profile');
    }
    $user = currentUser();
    $treeCount = (int)countUserTrees($user['id']);
    $canPublish = in_array($user['role'], ['user', 'admin'], true);
    page('Профиль и настройки', view('profile_view', [
        'user' => $user,
        'treeCount' => $treeCount,
        'canPublish' => $canPublish,
    ]), ['body' => 'plain-page']);
}

function pg_consent()
{
    $user = requireRole(['user', 'admin']);
    $isPost = isPost();
    if ($isPost) {
        $act = postStr('act');
        if ($act === 'agree') {
            if ((int)$user['consent'] !== 1) {
                db()->prepare('UPDATE users SET consent=1, consent_at=? WHERE id=?')->execute([nowSql(), (int)$user['id']]);
                flashSet('success', 'Согласие на обработку персональных данных получено. Теперь вы можете размещать сведения.');
            }
            redirect('consent');
        }
        if ($act === 'revoke') {
            if (postStr('confirm_text') !== 'УДАЛИТЬ') {
                flashSet('danger', 'Отзыв согласия не выполнен: введите текст «УДАЛИТЬ» для подтверждения.');
                redirect('consent');
            }
            if (!empty($_POST['understand'])) {
                deleteAllUserTrees($user['id']);
                db()->prepare('UPDATE users SET consent=0, consent_at=NULL WHERE id=?')->execute([(int)$user['id']]);
                flashSet('warning', 'Согласие отозвано. Все ваши данные удалены.');
                redirect('consent');
            }
            flashSet('danger', 'Отметьте галочку о понимании последствий отзыва согласия.');
            redirect('consent');
        }
        redirect('consent');
    }
    $user = currentUser();
    $trees = db()->prepare('SELECT id, title FROM trees WHERE owner_id=?')->execute([(int)$user['id']]);
    $treeList = db()->prepare('SELECT id, title, (SELECT COUNT(*) FROM persons p WHERE p.tree_id=t.id) AS persons_count FROM trees t WHERE owner_id=? ORDER BY title');
    $treeList->execute([(int)$user['id']]);
    page('Согласие на обработку персональных данных', view('consent_view', [
        'user' => $user,
        'treeList' => $treeList->fetchAll(),
    ]), ['body' => 'plain-page']);
}
