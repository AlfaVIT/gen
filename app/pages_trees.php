<?php

function pg_trees()
{
    $user = requireAuth();
    if (isPost()) {
        $title = postStr('title');
        if ($title === '') {
            flashSet('danger', 'Укажите название дерева.');
            redirect('trees');
        }
        if (!contentConsentRequired()) {
            flashSet('warning', 'Чтобы создать дерево, необходимо дать согласие на обработку персональных данных.');
            redirect('consent');
        }
        createTree($user['id'], $title, postStr('description'), postStr('is_public') === '1');
        flashSet('success', 'Дерево создано. Добавьте первую персону!');
        redirect('trees');
    }
    $trees = listTreesFor($user);
    $isGuest = $user['role'] === 'guest';
    $canCreate = contentConsentRequired();
    page('Деревья', view('trees_list', [
        'trees' => $trees,
        'user' => $user,
        'isGuest' => $isGuest,
        'canCreate' => $canCreate,
        'consentGiven' => (int)$user['consent'] === 1,
    ]), ['body' => 'plain-page']);
}

function treeStats($treeId)
{
    $st = db()->prepare('SELECT
        (SELECT COUNT(*) FROM persons WHERE tree_id=?) AS persons,
        (SELECT COUNT(*) FROM unions WHERE tree_id=?) AS unions,
        (SELECT COUNT(*) FROM media WHERE tree_id=?) AS media');
    $st->execute([(int)$treeId, (int)$treeId, (int)$treeId]);
    return $st->fetch();
}

function pg_tree()
{
    list($user, $tree) = requireTreeView(getStr('id'));
    $canEdit = canEditTree($user, $tree);
    $api = treeDataForApi($tree, $user);
    $rootCandidates = [];
    $idx = 0;
    foreach ($api['persons'] as $p) {
        $rootCandidates[] = $p['id'];
        $idx++;
        if ($idx > 500) break;
    }
    $persons = personsWithVisibility($tree, $user);
    page($tree['title'] . ' — дерево', view('tree_view', [
        'tree' => $tree,
        'user' => $user,
        'canEdit' => $canEdit,
        'stats' => treeStats($tree['id']),
        'rootCandidates' => $rootCandidates,
        'apiPersons' => $persons,
        'apiUnions' => $api['unions'],
        'isOwner' => isTreeOwnerOrAdmin($user, $tree),
        'maxUpload' => (int)cfg('upload_max_bytes'),
    ]), [
        'body' => 'plain-page',
        'js' => ['assets/js/tree.js'],
    ]);
}

function pg_tree_settings()
{
    $user = requireAuth();
    $tree = loadTree(getStr('id'));
    if (!$tree) {
        exit('Дерево не найдено.');
    }
    $canEdit = canEditTree($user, $tree);
    $ownerOrAdmin = isTreeOwnerOrAdmin($user, $tree);
    if (!$ownerOrAdmin) {
        flashSet('danger', 'Недостаточно прав для управления деревом.');
        redirect('tree&id=' . (int)$tree['id']);
    }
    if (isPost()) {
        $act = postStr('act');
        if ($act === 'update') {
            if (!$canEdit) {
                flashSet('warning', 'Необходимо согласие на обработку персональных данных для изменения дерева.');
                redirect('consent');
            }
            $title = postStr('title');
            if ($title === '') {
                flashSet('danger', 'Название не может быть пустым.');
            } else {
                updateTree($tree['id'], $title, postStr('description'), postStr('is_public') === '1');
                flashSet('success', 'Настройки дерева сохранены.');
            }
            redirect('tree_settings&id=' . (int)$tree['id']);
        }
        if ($act === 'import') {
            if (!contentConsentRequired()) {
                flashSet('warning', 'Для импорта необходимо согласие на обработку персональных данных.');
                redirect('consent');
            }
            if (empty($_FILES['xmlfile']) || !is_uploaded_file($_FILES['xmlfile']['tmp_name'])) {
                flashSet('danger', 'Выберите XML-файл для восстановления.');
                redirect('tree_settings&id=' . (int)$tree['id']);
            }
            $xml = (string)file_get_contents($_FILES['xmlfile']['tmp_name']);
            if (trim($xml) === '') {
                flashSet('danger', 'Файл пуст.');
                redirect('tree_settings&id=' . (int)$tree['id']);
            }
            try {
                $newId = importTreeFromXml($user['id'], $xml);
                flashSet('success', 'Дерево успешно восстановлено из XML.');
                redirect('tree&id=' . $newId);
            } catch (RuntimeException $ex) {
                flashSet('danger', 'Не удалось импортировать: ' . $ex->getMessage());
                redirect('tree_settings&id=' . (int)$tree['id']);
            }
        }
        if ($act === 'delete') {
            if ($canEdit && postStr('confirm_text') === 'УДАЛИТЬ') {
                deleteTree($tree['id']);
                flashSet('success', 'Дерево удалено.');
                redirect('trees');
            }
            flashSet('danger', 'Удаление отменено: введите «УДАЛИТЬ» для подтверждения.');
            redirect('tree_settings&id=' . (int)$tree['id']);
        }
        redirect('tree_settings&id=' . (int)$tree['id']);
    }
    page('Настройки дерева', view('tree_settings', [
        'tree' => $tree,
        'user' => $user,
        'canEdit' => $canEdit,
        'stats' => treeStats($tree['id']),
    ]), ['body' => 'plain-page']);
}

function pg_export()
{
    $user = requireAuth();
    $tree = loadTree(getStr('id'));
    if (!$tree) {
        exit('Дерево не найдено.');
    }
    if (!isTreeOwnerOrAdmin($user, $tree)) {
        flashSet('danger', 'Экспорт доступен только владельцу дерева.');
        redirect('tree&id=' . (int)$tree['id']);
    }
    try {
        $xml = exportTreeXml($tree['id'], true);
    } catch (RuntimeException $ex) {
        flashSet('danger', $ex->getMessage());
        redirect('tree&id=' . (int)$tree['id']);
    }
    $fname = preg_replace('/[^A-Za-z0-9А-Яа-яЁё _-]+/u', '_', $tree['title']);
    $fname = trim($fname, '_ ');
    if ($fname === '') {
        $fname = 'tree_' . $tree['id'];
    }
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '.xml"');
    echo $xml;
    exit;
}
