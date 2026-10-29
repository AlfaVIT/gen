<?php

function mediaCtx($mediaId)
{
    $media = getMedia((int)$mediaId);
    if (!$media) {
        return null;
    }
    $tree = loadTree($media['tree_id']);
    if (!$tree) {
        return null;
    }
    return [$media, $tree];
}

function bindingsViewData($media, $tree, $user)
{
    $ghostSet = array_flip(ghostPersonIds($tree, $user));
    $out = [];
    foreach (bindingsOfMedia($media['id']) as $b) {
        $ghost = isset($ghostSet[(int)$b['person_id']]);
        $out[] = [
            'id' => (int)$b['id'],
            'person_id' => (int)$b['person_id'],
            'person_name' => $ghost ? 'Персона' : $b['person_name'],
            'ghost' => $ghost,
            'btype' => $b['btype'],
            'page' => (int)$b['page'],
            'x' => (float)$b['x'],
            'y' => (float)$b['y'],
            'w' => (float)$b['w'],
            'h' => (float)$b['h'],
            'tstart' => (float)$b['tstart'],
            'tend' => (float)$b['tend'],
        ];
    }
    return $out;
}

function pg_media_add()
{
    $user = requireAuth();
    $tree = loadTree(getStr('tree'));
    if (!$tree || !canEditTree($user, $tree)) {
        flashSet('danger', 'Недостаточно прав.');
        redirect('trees');
    }
    $prePerson = (int)getStr('person');
    $archival = getStr('archival') === '1';
    $kindPreset = getStr('kind');
    if (isPost()) {
        $kind = postStr('kind');
        $allowedKinds = ['image', 'pdf', 'video', 'audio', 'note'];
        if (!in_array($kind, $allowedKinds, true)) {
            flashSet('danger', 'Неверный тип материала.');
            redirect('tree&id=' . (int)$tree['id']);
        }
        $data = [
            'title' => postStr('title'),
            'note' => postStr('note'),
            'archival' => $archival || postStr('archival') === '1',
            'url' => '',
        ];
        $fileMeta = null;
        $url = trim(postStr('url'));
        if ($kind === 'note') {
            if ($data['note'] === '' && $data['title'] === '') {
                flashSet('danger', 'Для текстовой записи заполните текст или название.');
                redirect('media_add&tree=' . (int)$tree['id'] . ($prePerson ? '&person=' . $prePerson : '') . ($archival ? '&archival=1' : '') . '&kind=' . urlencode($kind));
            }
        } elseif ($url !== '') {
            if (!preg_match('~^https?://~i', $url)) {
                flashSet('danger', 'Ссылка должна начинаться с http(s)://');
                redirect('media_add&tree=' . (int)$tree['id'] . ($prePerson ? '&person=' . $prePerson : '') . ($archival ? '&archival=1' : '') . '&kind=' . urlencode($kind));
            }
            $data['url'] = $url;
        } else {
            try {
                $fileMeta = mediaUploadFromPost((int)$tree['id'], 'file');
            } catch (RuntimeException $ex) {
                flashSet('danger', $ex->getMessage());
                redirect('media_add&tree=' . (int)$tree['id'] . ($prePerson ? '&person=' . $prePerson : '') . ($archival ? '&archival=1' : '') . '&kind=' . urlencode($kind));
            }
        }
        try {
            $mid = createMedia($tree['id'], $user['id'], $kind, $data, $fileMeta);
        } catch (RuntimeException $ex) {
            flashSet('danger', $ex->getMessage());
            redirect('tree&id=' . (int)$tree['id']);
        }
        $q = 'media_edit&id=' . $mid;
        if ($prePerson) {
            $p = getPerson($prePerson);
            if ($p && (int)$p['tree_id'] === (int)$tree['id'] && $kind !== 'note') {
                addBinding($mid, $prePerson, ['btype' => $kind === 'video' || $kind === 'audio' ? 'time' : ($kind === 'pdf' ? 'page' : 'region')]);
                $q .= '&b=';
            }
        }
        flashSet('success', 'Материал добавлен.' . ($kind === 'note' ? '' : ' Теперь отметьте область/таймкод, относящиеся к персоне.'));
        redirect($q);
    }
    page('Добавить материал', view('media_add', [
        'tree' => $tree,
        'prePerson' => $prePerson,
        'archival' => $archival,
        'kindPreset' => $kindPreset,
        'maxUpload' => (int)cfg('upload_max_bytes'),
    ]), ['body' => 'plain-page']);
}

function pg_media()
{
    $user = requireAuth();
    $ctx = mediaCtx(getStr('id'));
    if (!$ctx) {
        exit('Материал не найден.');
    }
    [$media, $tree] = $ctx;
    if (!canViewTree($user, $tree)) {
        http_response_code(403);
        exit('Доступ ограничен.');
    }
    $canEdit = canEditTree($user, $tree);
    $bindings = bindingsViewData($media, $tree, $user);
    $js = $media['kind'] === 'pdf' ? ['https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js', 'assets/js/viewer.js'] : ['assets/js/viewer.js'];
    page(($media['title'] ? $media['title'] : ($media['original'] ?: 'Материал')), view('media_view', [
        'media' => $media,
        'tree' => $tree,
        'user' => $user,
        'canEdit' => $canEdit,
        'bindings' => $bindings,
    ]), [
        'body' => 'plain-page',
        'js' => $js,
    ]);
}

function pg_media_edit()
{
    $user = requireAuth();
    $ctx = mediaCtx(getStr('id'));
    if (!$ctx) {
        exit('Материал не найден.');
    }
    [$media, $tree] = $ctx;
    if (!canEditTree($user, $tree)) {
        flashSet('danger', 'Недостаточно прав для изменения материала.');
        redirect('media&id=' . (int)$media['id']);
    }
    $activeB = (int)getStr('b');

    if (isPost()) {
        $act = postStr('act');
        if ($act === 'meta') {
            updateMediaMeta($media['id'], postStr('title'), postStr('note'), $archival = postStr('archival') === '1', postStr('url'));
            flashSet('success', 'Параметры материала сохранены.');
            redirect('media_edit&id=' . (int)$media['id']);
        }
        if ($act === 'binding_add') {
            $pid = (int)postStr('person_id');
            $p = $pid ? getPerson($pid) : false;
            if ($p && (int)$p['tree_id'] === (int)$tree['id']) {
                $btype = $media['kind'] === 'video' || $media['kind'] === 'audio' ? 'time' : ($media['kind'] === 'pdf' ? 'page' : 'region');
                addBinding($media['id'], $pid, ['btype' => $btype]);
                flashSet('success', 'Привязка создана. Определите область или таймкод.');
                $bind = db()->lastInsertId();
                redirect('media_edit&id=' . (int)$media['id'] . '&b=' . $bind);
            }
            flashSet('danger', 'Выберите персону из дерева.');
            redirect('media_edit&id=' . (int)$media['id']);
        }
        if ($act === 'binding_update') {
            $bid = (int)postStr('binding_id');
            $b = getBinding($bid);
            if ($b && (int)$b['media_id'] === (int)$media['id']) {
                updateBinding($bid, [
                    'btype' => postStr('btype'),
                    'page' => (int)postStr('page'),
                    'x' => postStr('x'),
                    'y' => postStr('y'),
                    'w' => postStr('w'),
                    'h' => postStr('h'),
                    'tstart' => postStr('tstart'),
                    'tend' => postStr('tend'),
                ]);
                flashSet('success', 'Привязка обновлена.');
                redirect('media_edit&id=' . (int)$media['id']);
            }
            redirect('media_edit&id=' . (int)$media['id']);
        }
        if ($act === 'binding_delete') {
            $bid = (int)postStr('binding_id');
            deleteBinding($bid);
            flashSet('success', 'Привязка удалена.');
            redirect('media_edit&id=' . (int)$media['id']);
        }
        if ($act === 'media_delete') {
            if (postStr('confirm_text') === 'УДАЛИТЬ') {
                deleteMediaRow($media['id']);
                flashSet('success', 'Материал удалён.');
                redirect('archive&tree=' . (int)$tree['id']);
            }
            flashSet('danger', 'Удаление отменено.');
            redirect('media_edit&id=' . (int)$media['id']);
        }
        redirect('media_edit&id=' . (int)$media['id']);
    }

    $persons = listPersons($tree['id']);
    $bindings = bindingsOfMedia($media['id']);
    $ghostSet = array_flip(ghostPersonIds($tree, $user));
    $viewData = [];
    foreach ($bindings as $b) {
        $viewData[] = [
            'id' => (int)$b['id'],
            'person_id' => (int)$b['person_id'],
            'person_name' => isset($ghostSet[(int)$b['person_id']]) ? 'Персона' : $b['person_name'],
            'ghost' => isset($ghostSet[(int)$b['person_id']]),
            'btype' => $b['btype'],
            'page' => (int)$b['page'],
            'x' => (float)$b['x'],
            'y' => (float)$b['y'],
            'w' => (float)$b['w'],
            'h' => (float)$b['h'],
            'tstart' => (float)$b['tstart'],
            'tend' => (float)$b['tend'],
        ];
    }
    $activeBinding = null;
    foreach ($viewData as $vb) {
        if ($vb['id'] === $activeB) {
            $activeBinding = $vb;
            break;
        }
    }
    if ($activeB && !$activeBinding) {
        $activeBinding = $viewData ? $viewData[0] : null;
    }
    $js = $media['kind'] === 'pdf' ? ['https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js', 'assets/js/bind.js'] : ['assets/js/bind.js'];
    page('Редактирование материала', view('media_edit', [
        'media' => $media,
        'tree' => $tree,
        'canEdit' => true,
        'persons' => $persons,
        'bindings' => $viewData,
        'activeBinding' => $activeBinding,
        'fileUrl' => $media['file'] ? mediaViewUrl($media['file']) : '',
    ]), [
        'body' => 'plain-page',
        'js' => $js,
    ]);
}

function pg_archive()
{
    $user = requireAuth();
    $tree = loadTree(getStr('tree'));
    if (!$tree) {
        exit('Дерево не найдено.');
    }
    if (!canViewTree($user, $tree)) {
        http_response_code(403);
        exit('Доступ ограничен.');
    }
    $canEdit = canEditTree($user, $tree);
    $view = getStr('view', 'archive');
    $archivalOnly = $view === 'all' ? null : true;
    $mediaItems = listTreeMedia($tree['id'], $archivalOnly);
    $ghostSet = array_flip(ghostPersonIds($tree, $user));

    $rows = [];
    foreach ($mediaItems as $m) {
        $binds = bindingsOfMedia($m['id']);
        $sum = [];
        foreach ($binds as $b) {
            $g = isset($ghostSet[(int)$b['person_id']]);
            $sum[] = [
                'person_id' => (int)$b['person_id'],
                'person_name' => $g ? 'Персона' : $b['person_name'],
                'ghost' => $g,
                'btype' => $b['btype'],
                'page' => (int)$b['page'],
                'tstart' => (float)$b['tstart'],
                'tend' => (float)$b['tend'],
            ];
        }
        $rows[] = ['media' => $m, 'bindings' => $sum];
    }
    page('Архивные данные — ' . $tree['title'], view('archive_list', [
        'tree' => $tree,
        'user' => $user,
        'canEdit' => $canEdit,
        'rows' => $rows,
        'view' => $view,
        'archiveCount' => count(listTreeMedia($tree['id'], true)),
    ]), ['body' => 'plain-page']);
}
