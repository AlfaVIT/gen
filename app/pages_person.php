<?php

function personTreeContext($personId)
{
    $person = getPerson((int)$personId);
    if (!$person) {
        return null;
    }
    $tree = loadTree($person['tree_id']);
    if (!$tree) {
        return null;
    }
    return [$person, $tree];
}

function pg_person()
{
    $user = requireAuth();
    $ctx = personTreeContext(getStr('id'));
    if (!$ctx) {
        exit('Персона не найдена.');
    }
    [$person, $tree] = $ctx;
    if (!canViewTree($user, $tree)) {
        http_response_code(403);
        exit('Доступ к дереву ограничен.');
    }
    $canEdit = canEditTree($user, $tree);
    $ghost = !isTreeOwnerOrAdmin($user, $tree) && in_array((int)$person['id'], ghostPersonIds($tree, $user), true);

    if (isPost() && !$ghost) {
        handlePersonActions($person, $user, $canEdit);
    }

    $person = getPerson($person['id']);
    $unionsMine = personUnions($person['id']);
    $childUnions = personChildUnions($person['id']);
    $children = personChildren($person['id']);
    $parents = personParents($person['id']);
    $spouses = personSpouses($person['id']);
    $bindings = $ghost ? [] : bindingsOfPerson($person['id']);
    $materials = [];
    $archival = [];
    foreach ($bindings as $b) {
        $entry = [
            'media_id' => $b['media_id'],
            'kind' => $b['kind'],
            'title' => $b['title'],
            'note' => $b['note'],
            'file' => $b['file'],
            'url' => $b['url'],
            'archival' => (int)$b['archival'],
            'btype' => $b['btype'],
            'page' => $b['page'],
            'x' => $b['x'],
            'y' => $b['y'],
            'w' => $b['w'],
            'h' => $b['h'],
            'tstart' => $b['tstart'],
            'tend' => $b['tend'],
            'binding_id' => $b['id'],
            'binding_comment' => '',
            'original' => $b['original'],
            'mime' => $b['mime'],
            'size' => $b['size'],
        ];
        if ($entry['archival']) {
            $archival[] = $entry;
        } else {
            $materials[] = $entry;
        }
    }
    $siblings = [];
    foreach ($childUnions as $u) {
        foreach (unionChildren($u['id']) as $c) {
            if ((int)$c['id'] !== (int)$person['id']) {
                $siblings[] = $c;
            }
        }
    }
    $siblings = array_unique($siblings, SORT_REGULAR);

    $personsList = $ghost ? [] : listPersons($person['tree_id']);
    $ghostSet = $ghost ? [true] : [];

    $socials = json_decode($person['social'] ?: '[]', true) ?: [];

    page(($ghost ? 'Скрытая персона' : $person['name']), view('person_view', [
        'person' => $person,
        'tree' => $tree,
        'user' => $user,
        'canEdit' => $canEdit,
        'ghost' => $ghost,
        'isOwner' => isTreeOwnerOrAdmin($user, $tree),
        'unionsMine' => $unionsMine,
        'childUnions' => $childUnions,
        'children' => $children,
        'parents' => $parents,
        'spouses' => $spouses,
        'siblings' => $siblings,
        'materials' => $materials,
        'archival' => $archival,
        'personsList' => $personsList,
        'socials' => $socials,
    ]), ['body' => 'plain-page']);
}

function personSocialFromPost()
{
    $labels = isset($_POST['soc_label']) ? (array)$_POST['soc_label'] : [];
    $urls = isset($_POST['soc_url']) ? (array)$_POST['soc_url'] : [];
    $out = [];
    foreach ($labels as $i => $l) {
        if (!isset($urls[$i])) continue;
        $url = trim((string)$urls[$i]);
        if ($url === '') continue;
        $out[] = ['label' => trim((string)$l), 'url' => $url];
    }
    return $out;
}

function handlePersonActions($person, $user, $canEdit)
{
    $act = postStr('act');
    if (!$canEdit) {
        flashSet('warning', 'Для изменений необходимо согласие на обработку персональных данных.');
        redirect('person&id=' . (int)$person['id']);
    }
    $treeId = (int)$person['tree_id'];
    $back = 'person&id=' . (int)$person['id'];

    if ($act === 'update' || $act === 'delete') {
        pg_person_edit_post($person);
        return;
    }

    if ($act === 'spouse_create') {
        $name = postStr('sp_name');
        if ($name === '') {
            flashSet('danger', 'Укажите имя супруга(и).');
        } else {
            $sid = createPerson($treeId, [
                'name' => $name,
                'gender' => postStr('sp_gender', 'u'),
                'birth_date' => postStr('sp_birth'),
                'death_date' => postStr('sp_death'),
                'is_public' => 1,
            ]);
            linkSpouse($person['id'], $sid, postStr('sp_start'), postStr('sp_end'), postStr('sp_note'));
            flashSet('success', 'Супруг(а) добавлен(а).');
        }
        redirect($back);
    }
    if ($act === 'spouse_link') {
        $sid = (int)postStr('existing_spouse');
        $p = $sid ? getPerson($sid) : false;
        if (!$p || (int)$p['tree_id'] !== $treeId || (int)$sid === (int)$person['id']) {
            flashSet('danger', 'Выберите персону из дерева.');
        } else {
            if (spouseUnionBetween($treeId, $person['id'], $sid)) {
                flashSet('warning', 'Эти персоны уже состоят в союзе.');
            } else {
                linkSpouse($person['id'], $sid, postStr('sp_start'), postStr('sp_end'), postStr('sp_note'));
                flashSet('success', 'Союз создан.');
            }
        }
        redirect($back);
    }
    if ($act === 'spouse_link_in_union') {
        $sid = (int)postStr('person_id');
        $p = $sid ? getPerson($sid) : false;
        if (!$p || (int)$p['tree_id'] !== $treeId || (int)$sid === (int)$person['id']) {
            flashSet('danger', 'Выберите персону из дерева.');
        } elseif (spouseUnionBetween($treeId, $person['id'], $sid)) {
            flashSet('warning', 'Эти персоны уже состоят в союзе.');
        } else {
            linkSpouse($person['id'], $sid, '', '', '');
            flashSet('success', 'Создан отдельный брачный союз.');
        }
        redirect($back);
    }

    if ($act === 'child_add') {
        $uid = (int)postStr('union_id');
        $u = $uid ? getUnion($uid) : false;
        if (!$u || (int)$u['tree_id'] !== $treeId) {
            $uid = ensureSelfUnion($person['id']);
        }
        $name = postStr('ch_name');
        if ($name === '') {
            flashSet('danger', 'Укажите имя ребёнка.');
            redirect($back);
        }
        $cid = createPerson($treeId, [
            'name' => $name,
            'gender' => postStr('ch_gender', 'u'),
            'birth_date' => postStr('ch_birth'),
            'birth_place' => postStr('ch_birth_place'),
            'death_date' => postStr('ch_death'),
            'is_public' => 1,
        ]);
        try {
            attachParents($cid, unionMemberIds($uid));
            flashSet('success', 'Ребёнок добавлен в семью.');
        } catch (RuntimeException $ex) {
            flashSet('danger', $ex->getMessage());
        }
        redirect($back);
    }
    if ($act === 'child_link') {
        $uid = (int)postStr('union_id');
        $cid = (int)postStr('existing_child');
        $u = $uid ? getUnion($uid) : false;
        if (!$u || (int)$u['tree_id'] !== $treeId) {
            flashSet('danger', 'Союз не найден.');
            redirect($back);
        }
        $cp = $cid ? getPerson($cid) : false;
        if (!$cp || (int)$cp['tree_id'] !== $treeId) {
            flashSet('danger', 'Выберите персону.');
            redirect($back);
        }
        try {
            attachParents($cid, unionMemberIds($uid));
            flashSet('success', 'Ребёнок добавлен в семью.');
        } catch (RuntimeException $ex) {
            flashSet('danger', $ex->getMessage());
        }
        redirect($back);
    }

    if ($act === 'parents_attach') {
        $fid = (int)postStr('father_id');
        $mid = (int)postStr('mother_id');
        $ids = array_values(array_filter([$fid, $mid]));
        if (!$ids) {
            flashSet('danger', 'Выберите отца и/или мать из списка.');
            redirect($back);
        }
        foreach ([['id' => $fid, 'role' => 'm', 'label' => 'Отец'], ['id' => $mid, 'role' => 'f', 'label' => 'Мать']] as $item) {
            if (!$item['id']) continue;
            $pp = getPerson($item['id']);
            if (!$pp || (int)$pp['tree_id'] !== $treeId) {
                flashSet('danger', $item['label'] . ' не найден в этом дереве.');
                redirect($back);
            }
            if ($pp['gender'] !== $item['role']) {
                flashSet('danger', 'Для роли «' . $item['label'] . '» выберите персону подходящего пола.');
                redirect($back);
            }
        }
        try {
            $existing = personChildUnions((int)$person['id']);
            if ($existing) {
                $u = $existing[0];
                $memberIds = array_map('intval', array_column(unionMembers($u['id']), 'id'));
                foreach ($ids as $pid) {
                    if ($pid === (int)$person['id']) {
                        continue;
                    }
                    if (in_array($pid, $memberIds, true)) {
                        continue;
                    }
                    if (isAncestor($treeId, $pid, (int)$person['id']) || isAncestor($treeId, (int)$person['id'], $pid)) {
                        throw new RuntimeException('Выбранный человек уже есть в родословной этой персоны (выберите другого родителя).');
                    }
                    unionAddMember($u['id'], $pid);
                    $memberIds[] = $pid;
                }
                flashSet('success', 'Отец и мать указаны (ближайшие предки).');
            } else {
                attachParents((int)$person['id'], $ids);
                flashSet('success', 'Отец и мать указаны (ближайшие предки).');
            }
        } catch (RuntimeException $ex) {
            flashSet('danger', $ex->getMessage());
        }
        redirect($back);
    }

    if ($act === 'union_save') {
        $uid = (int)postStr('union_id');
        $u = getUnion($uid);
        if ($u && (int)$u['tree_id'] === $treeId) {
            unionUpdate($uid, postStr('u_start'), postStr('u_end'), postStr('u_note'));
            flashSet('success', 'Данные союза сохранены.');
        }
        redirect($back);
    }
    if ($act === 'union_remove_member') {
        $uid = (int)postStr('union_id');
        $mid = (int)postStr('person_id');
        $u = getUnion($uid);
        if ($u && (int)$u['tree_id'] === $treeId && $mid !== (int)$person['id']) {
            unionRemoveMember($uid, $mid);
            flashSet('success', 'Человек удалён из союза.');
        }
        redirect($back);
    }
    if ($act === 'union_remove_child') {
        $uid = (int)postStr('union_id');
        $cid = (int)postStr('person_id');
        $u = getUnion($uid);
        if ($u && (int)$u['tree_id'] === $treeId) {
            unionRemoveChild($uid, $cid);
            flashSet('success', 'Связь ребёнка с этой семьёй удалена.');
        }
        redirect($back);
    }
    if ($act === 'union_delete') {
        $uid = (int)postStr('union_id');
        $u = getUnion($uid);
        if ($u && (int)$u['tree_id'] === $treeId && postStr('confirm_text') === 'УДАЛИТЬ') {
            unionDelete($uid);
            flashSet('success', 'Союз удалён.');
        }
        redirect($back);
    }
    if ($act === 'remove_parent') {
        $uid = (int)postStr('union_id');
        $pid = (int)postStr('person_id');
        $u = getUnion($uid);
        if ($u && (int)$u['tree_id'] === $treeId && (int)$person['id'] !== 0) {
            unionRemoveMember($uid, $pid);
            flashSet('success', 'Связь с родителем удалена.');
        }
        redirect($back);
    }
    redirect($back);
}

function unionMemberIds($unionId)
{
    return array_map('intval', array_column(unionMembers($unionId), 'id'));
}

function ensureSelfUnion($personId)
{
    $uid = unionCreate(getPerson($personId)['tree_id'], '', '', '');
    unionAddMember($uid, $personId);
    return $uid;
}

function linkSpouse($aId, $bId, $start, $end, $note)
{
    $treeId = (int)getPerson($aId)['tree_id'];
    $uid = unionCreate($treeId, $start, $end, $note);
    unionAddMember($uid, $aId);
    unionAddMember($uid, $bId);
}

function pg_person_new()
{
    $user = requireAuth();
    $tree = loadTree(getStr('tree'));
    if (!$tree) {
        exit('Дерево не найдено.');
    }
    if (!contentConsentRequired()) {
        flashSet('warning', 'Чтобы добавить персону, необходимо дать согласие на обработку персональных данных.');
        redirect('consent');
    }
    if (!canEditTree($user, $tree)) {
        flashSet('danger', 'Недостаточно прав.');
        redirect('tree&id=' . (int)$tree['id']);
    }
    if (isPost()) {
        try {
            $id = createPerson($tree['id'], [
                'name' => postStr('name'),
                'gender' => postStr('gender', 'u'),
                'birth_date' => postStr('birth_date'),
                'birth_place' => postStr('birth_place'),
                'death_date' => postStr('death_date'),
                'death_place' => postStr('death_place'),
                'is_public' => postStr('is_public') === '1',
                'description' => postStr('description'),
                'social' => personSocialFromPost(),
            ]);
            $parentOf = (int)getStr('parent_of');
            if ($parentOf) {
                $parent = getPerson($parentOf);
                if ($parent && (int)$parent['tree_id'] === (int)$tree['id']) {
                    try {
                        attachParents($parentOf, [$id]);
                    } catch (RuntimeException $ex) {
                        flashSet('warning', 'Персона создана, но привязка к родителю не выполнена: ' . $ex->getMessage());
                    }
                }
            }
            flashSet('success', 'Персона добавлена в дерево.');
            redirect('person&id=' . $id);
        } catch (RuntimeException $ex) {
            flashSet('danger', $ex->getMessage());
            redirect('person_new&tree=' . (int)$tree['id']);
        }
    }
    page('Новая персона', view('person_form', [
        'tree' => $tree,
        'mode' => 'new',
        'person' => null,
        'socials' => [],
    ]), ['body' => 'plain-page']);
}

function pg_person_edit()
{
    $user = requireAuth();
    $person = getPerson(getStr('id'));
    if (!$person) {
        exit('Персона не найдена.');
    }
    $tree = loadTree($person['tree_id']);
    if (!$tree || !canEditTree($user, $tree)) {
        flashSet('danger', 'Недостаточно прав.');
        redirect('trees');
    }
    if (isPost()) {
        pg_person_edit_post($person);
        return;
    }
    $socials = json_decode($person['social'] ?: '[]', true) ?: [];
    page('Редактирование персоны', view('person_form', [
        'tree' => $tree,
        'mode' => 'edit',
        'person' => $person,
        'socials' => $socials,
    ]), ['body' => 'plain-page']);
}

function pg_person_edit_post($person)
{
    $tree = loadTree($person['tree_id']);
    $act = postStr('act', 'update');
    if ($act === 'update') {
        try {
            updatePerson($person['id'], [
                'name' => postStr('name'),
                'gender' => postStr('gender', 'u'),
                'birth_date' => postStr('birth_date'),
                'birth_place' => postStr('birth_place'),
                'death_date' => postStr('death_date'),
                'death_place' => postStr('death_place'),
                'is_public' => postStr('is_public') === '1',
                'description' => postStr('description'),
                'social' => personSocialFromPost(),
            ]);
            flashSet('success', 'Данные персоны сохранены.');
        } catch (RuntimeException $ex) {
            flashSet('danger', $ex->getMessage());
        }
        redirect('person&id=' . (int)$person['id']);
    }
    if ($act === 'delete') {
        if (postStr('confirm_text') === 'УДАЛИТЬ') {
            deletePerson($person['id']);
            flashSet('success', 'Персона удалена из дерева.');
            redirect('tree&id=' . (int)$person['tree_id']);
        }
        flashSet('danger', 'Удаление отменено: введите «УДАЛИТЬ».');
        redirect('person&id=' . (int)$person['id']);
    }
    redirect('person&id=' . (int)$person['id']);
}
