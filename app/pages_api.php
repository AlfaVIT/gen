<?php

function pg_api_tree()
{
    $user = requireAuth();
    $tree = loadTree(getStr('id'));
    if (!$tree) {
        jsonOut(['error' => 'not found'], 404);
    }
    if (!canViewTree($user, $tree)) {
        jsonOut(['error' => 'forbidden'], 403);
    }
    jsonOut(treeDataForApi($tree, $user));
}

function pg_api_media_persons()
{
    $user = requireAuth();
    $tree = loadTree(getStr('tree'));
    if (!$tree) {
        jsonOut(['error' => 'not found'], 404);
    }
    if (!canViewTree($user, $tree)) {
        jsonOut(['error' => 'forbidden'], 403);
    }
    $ghost = array_flip(ghostPersonIds($tree, $user));
    $out = [];
    foreach (listPersons($tree['id']) as $p) {
        if (isset($ghost[(int)$p['id']])) continue;
        $out[] = ['id' => (int)$p['id'], 'name' => $p['name']];
    }
    jsonOut(['persons' => $out]);
}

function attachChildToFocus($focusId, $childId)
{
    $u = null;
    foreach (personUnions($focusId) as $pu) {
        if (trim($pu['end_date']) === '') {
            $u = $pu;
            break;
        }
    }
    if (!$u) {
        $uid = ensureSelfUnion($focusId);
        unionAddChild($uid, $childId);
        return;
    }
    unionAddChild((int)$u['id'], $childId);
}

function pg_api_rel_add()
{
    $user = requireAuth();
    $tree = loadTree(postStr('tree'));
    if (!$tree) {
        jsonOut(['ok' => false, 'error' => 'Дерево не найдено.'], 404);
    }
    if (!canEditTree($user, $tree)) {
        jsonOut(['ok' => false, 'error' => 'Недостаточно прав.'], 403);
    }
    $focus = (int)postStr('person');
    $fx = getPerson($focus);
    if (!$fx || (int)$fx['tree_id'] !== (int)$tree['id']) {
        jsonOut(['ok' => false, 'error' => 'Персона не найдена.'], 400);
    }
    $rel = postStr('relation');
    $name = postStr('name');
    if ($name === '') {
        jsonOut(['ok' => false, 'error' => 'Укажите имя.'], 400);
    }
    if (!in_array($rel, ['spouse', 'child', 'parent'], true)) {
        jsonOut(['ok' => false, 'error' => 'Неверный тип связи.'], 400);
    }
    $gender = in_array(postStr('gender', 'u'), ['m', 'f', 'u'], true) ? postStr('gender', 'u') : 'u';
    try {
        if ($rel === 'spouse') {
            $sid = createPerson($tree['id'], [
                'name' => $name, 'gender' => $gender,
                'birth_date' => postStr('birth'), 'is_public' => 1,
            ]);
            linkSpouse($focus, $sid, '', '', '');
        } elseif ($rel === 'child') {
            $cid = createPerson($tree['id'], [
                'name' => $name, 'gender' => $gender,
                'birth_date' => postStr('birth'), 'death_date' => postStr('death'), 'is_public' => 1,
            ]);
            attachChildToFocus($focus, $cid);
        } else {
            $pid = createPerson($tree['id'], [
                'name' => $name, 'gender' => $gender,
                'birth_date' => postStr('birth'), 'is_public' => 1,
            ]);
            attachParents($focus, [$pid]);
        }
    } catch (RuntimeException $ex) {
        jsonOut(['ok' => false, 'error' => $ex->getMessage()], 400);
    }
    jsonOut(['ok' => true, 'data' => treeDataForApi($tree, $user)]);
}

function findPersonByNameGender($treeId, $name, $gender, $excludeId = 0)
{
    $st = db()->prepare('SELECT * FROM persons WHERE tree_id = ? AND gender = ? AND LOWER(name) = LOWER(?) AND id <> ? LIMIT 1');
    $st->execute([(int)$treeId, $gender, $name, (int)$excludeId]);
    return $st->fetch();
}

function setParentForChild($childId, $parentId, $gender)
{
    $treeId = (int)getPerson($childId)['tree_id'];
    if ((int)$parentId === (int)$childId) {
        throw new RuntimeException('Нельзя указать саму персону в качестве родителя.');
    }
    if (isAncestor($treeId, $parentId, (int)$childId) || isAncestor($treeId, (int)$childId, $parentId)) {
        throw new RuntimeException('Выбранный человек уже есть в родословной этой персоны.');
    }
    $existing = personChildUnions((int)$childId);
    if ($existing) {
        $u = $existing[0];
        foreach (unionMembers($u['id']) as $m) {
            if ($m['gender'] === $gender && (int)$m['id'] !== (int)$parentId) {
                unionRemoveMember($u['id'], (int)$m['id']);
            }
        }
        $memberIds = array_map('intval', array_column(unionMembers($u['id']), 'id'));
        if (!in_array((int)$parentId, $memberIds, true)) {
            unionAddMember($u['id'], (int)$parentId);
        }
    } else {
        attachParents((int)$childId, [(int)$parentId]);
    }
}

function pg_api_set_parent()
{
    $user = requireAuth();
    $tree = loadTree(postStr('tree'));
    if (!$tree) {
        jsonOut(['ok' => false, 'error' => 'Дерево не найдено.'], 404);
    }
    if (!canEditTree($user, $tree)) {
        jsonOut(['ok' => false, 'error' => 'Недостаточно прав.'], 403);
    }
    $childId = (int)postStr('person');
    $child = getPerson($childId);
    if (!$child || (int)$child['tree_id'] !== (int)$tree['id']) {
        jsonOut(['ok' => false, 'error' => 'Персона не найдена.'], 400);
    }
    $role = postStr('role');
    if (!in_array($role, ['father', 'mother'], true)) {
        jsonOut(['ok' => false, 'error' => 'Неверная роль родителя.'], 400);
    }
    $gender = $role === 'father' ? 'm' : 'f';
    $name = trim(postStr('name'));
    $pid = (int)postStr('parent_id');
    if ($pid > 0) {
        $par = getPerson($pid);
        if (!$par || (int)$par['tree_id'] !== (int)$tree['id'] || $par['gender'] !== $gender) {
            jsonOut(['ok' => false, 'error' => 'Выбранная персона не подходит по роли.'], 400);
        }
    } else {
        if ($name === '') {
            jsonOut(['ok' => false, 'error' => 'Укажите имя родителя.'], 400);
        }
        $par = findPersonByNameGender($tree['id'], $name, $gender, $childId);
        if (!$par) {
            $pid = createPerson($tree['id'], ['name' => $name, 'gender' => $gender, 'is_public' => 1]);
            $par = getPerson($pid);
        } else {
            $pid = (int)$par['id'];
        }
    }
    try {
        setParentForChild($childId, $pid, $gender);
    } catch (RuntimeException $ex) {
        jsonOut(['ok' => false, 'error' => $ex->getMessage()], 400);
    }
    jsonOut(['ok' => true, 'person' => ['id' => (int)$par['id'], 'name' => $par['name'], 'gender' => $par['gender']]]);
}

function avatarContext()
{
    $user = requireAuth();
    $person = getPerson((int)postStr('person'));
    if (!$person) {
        jsonOut(['ok' => false, 'error' => 'Персона не найдена.'], 400);
    }
    $tree = loadTree($person['tree_id']);
    if (!$tree || !canEditTree($user, $tree)) {
        jsonOut(['ok' => false, 'error' => 'Недостаточно прав.'], 403);
    }
    return [$user, $person, $tree];
}

function pg_avatar_upload()
{
    list($user, $person, $tree) = avatarContext();
    try {
        $rel = avatarUploadFromPost($person['tree_id'], $person['id'], 'photo');
    } catch (RuntimeException $ex) {
        jsonOut(['ok' => false, 'error' => $ex->getMessage()], 400);
    }
    jsonOut(['ok' => true, 'full' => $rel, 'full_url' => mediaViewUrl($rel)]);
}

function pg_avatar_save()
{
    list($user, $person, $tree) = avatarContext();
    $full = postStr('full');
    $fullReal = mediaFileReal($full);
    if (!$fullReal) {
        jsonOut(['ok' => false, 'error' => 'Исходное фото не найдено.'], 400);
    }
    $x = (int)postStr('x');
    $y = (int)postStr('y');
    $size = (int)postStr('size');
    if ($size <= 0) {
        jsonOut(['ok' => false, 'error' => 'Не выбрана область.'], 400);
    }
    $dir = avatarTreeDir($person['tree_id']);
    $name = 'av_' . (int)$person['id'] . '_' . bin2hex(random_bytes(8)) . '.jpg';
    $dst = $dir . '/' . $name;
    $rel = 'uploads/t' . (int)$person['tree_id'] . '/' . $name;
    try {
        avatarCropSquareTo($fullReal, $dst, $x, $y, $size, 50);
    } catch (RuntimeException $ex) {
        @unlink($dst);
        jsonOut(['ok' => false, 'error' => $ex->getMessage()], 400);
    }
    $oldAvatar = $person['avatar'];
    $oldFull = $person['avatar_full'];
    db()->prepare('UPDATE persons SET avatar = ?, avatar_full = ? WHERE id = ?')->execute([$rel, $full, (int)$person['id']]);
    if ($oldAvatar !== '' && $oldAvatar !== $rel) {
        deleteMediaFile($oldAvatar);
    }
    if ($oldFull !== '' && $oldFull !== $full) {
        deleteMediaFile($oldFull);
    }
    jsonOut(['ok' => true, 'avatar_url' => mediaViewUrl($rel)]);
}
