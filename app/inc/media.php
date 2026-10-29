<?php

function ghostPersonIds($tree, $user)
{
    if (!$user || (int)$tree['owner_id'] === (int)$user['id'] || $user['role'] === 'admin') {
        return [];
    }
    $treeId = (int)$tree['id'];
    $st = db()->prepare('SELECT id FROM persons WHERE tree_id = ? AND is_public = 0');
    $st->execute([$treeId]);
    $hidden = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    if (!$hidden) {
        return [];
    }
    $children = [];
    foreach (listUnions($treeId) as $u) {
        $members = unionMembers($u['id']);
        $kids = unionChildren($u['id']);
        foreach ($members as $m) {
            foreach ($kids as $k) {
                $children[(int)$m['id']][] = (int)$k['id'];
            }
        }
    }
    $ghost = [];
    $queue = $hidden;
    while ($queue) {
        $cur = array_shift($queue);
        if (isset($ghost[$cur])) continue;
        $ghost[$cur] = true;
        foreach ($children[$cur] ?? [] as $next) {
            if (!isset($ghost[$next])) {
                $queue[] = $next;
            }
        }
    }
    return array_keys($ghost);
}

function personsWithVisibility($tree, $user)
{
    $persons = listPersons((int)$tree['id']);
    $ghost = array_flip(ghostPersonIds($tree, $user));
    $result = [];
    foreach ($persons as $p) {
        $p['ghost'] = isset($ghost[(int)$p['id']]) ? 1 : 0;
        if ($p['ghost']) {
            $p['name_view'] = 'Персона';
            $p['visible_name'] = '';
        } else {
            $p['name_view'] = $p['name'];
            $p['visible_name'] = $p['name'];
        }
        $result[] = $p;
    }
    return $result;
}

function treeDataForApi($tree, $user)
{
    $persons = personsWithVisibility($tree, $user);
    $byId = [];
    foreach ($persons as $p) {
        $byId[(int)$p['id']] = $p;
    }
    $unions = [];
    foreach (listUnions((int)$tree['id']) as $u) {
        $members = array_map('intval', array_column(unionMembers($u['id']), 'id'));
        $kids = array_map('intval', array_column(unionChildren($u['id']), 'id'));
        $unions[] = [
            'id' => (int)$u['id'],
            'members' => $members,
            'children' => $kids,
            'start' => $u['start_date'],
            'end' => $u['end_date'],
        ];
    }
    $nameById = function ($pid) use ($byId) {
        if (!isset($byId[(int)$pid])) {
            return null;
        }
        $p = $byId[(int)$pid];
        return $p['ghost'] ? '' : $p['name'];
    };
    $data = [];
    foreach ($persons as $p) {
        $isGhost = (int)$p['ghost'] === 1;
        $data['persons'][] = [
            'id' => (int)$p['id'],
            'name' => $isGhost ? '' : $p['name'],
            'placeholder' => $isGhost,
            'gender' => $p['gender'],
            'birth' => $isGhost ? '' : $p['birth_date'],
            'death' => $isGhost ? '' : $p['death_date'],
            'avatar' => $isGhost ? '' : ($p['avatar'] ?? ''),
        ];
    }
    $data['unions'] = $unions;
    $data['canEdit'] = canEditTree($user, $tree) ? 1 : 0;
    $data['personsNameMap'] = [];
    foreach ($byId as $id => $p) {
        $data['personsNameMap'][$id] = $nameById($id);
    }
    return $data;
}

function createMedia($treeId, $userId, $kind, $data, $fileMeta)
{
    $allowed = ['image', 'video', 'audio', 'pdf', 'note'];
    if (!in_array($kind, $allowed, true)) {
        throw new RuntimeException('Неизвестный тип материала.');
    }
    $st = db()->prepare('INSERT INTO media (tree_id, kind, title, note, archival, url, file, original, mime, size, created_by, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $st->execute([
        (int)$treeId,
        $kind,
        trim($data['title'] ?? ''),
        trim($data['note'] ?? ''),
        !empty($data['archival']) ? 1 : 0,
        trim($data['url'] ?? ''),
        $fileMeta ? $fileMeta['file'] : '',
        $fileMeta ? $fileMeta['original'] : '',
        $fileMeta ? $fileMeta['mime'] : '',
        $fileMeta ? $fileMeta['size'] : 0,
        (int)$userId,
        nowSql(),
    ]);
    return (int)db()->lastInsertId();
}

function updateMediaMeta($id, $title, $note, $archival, $url)
{
    $st = db()->prepare('UPDATE media SET title=?, note=?, archival=?, url=? WHERE id=?');
    $st->execute([trim($title), trim($note), $archival ? 1 : 0, trim($url), (int)$id]);
}

function getMedia($id)
{
    $st = db()->prepare('SELECT * FROM media WHERE id=?');
    $st->execute([(int)$id]);
    return $st->fetch();
}

function listTreeMedia($treeId, $archivalOnly = null)
{
    $sql = 'SELECT m.*, (SELECT COUNT(*) FROM media_bindings b WHERE b.media_id = m.id) AS bindings_count
            FROM media m WHERE m.tree_id = ?';
    if ($archivalOnly !== null) {
        $sql .= ' AND m.archival = ' . ($archivalOnly ? 1 : 0);
    }
    $sql .= ' ORDER BY m.archival DESC, m.created_at DESC';
    $st = db()->prepare($sql);
    $st->execute([(int)$treeId]);
    return $st->fetchAll();
}

function deleteMediaRow($id)
{
    $m = getMedia($id);
    if (!$m) return;
    if ($m['file']) {
        deleteMediaFile($m['file']);
    }
    db()->prepare('DELETE FROM media WHERE id=?')->execute([(int)$id]);
}

function addBinding($mediaId, $personId, $data)
{
    $st = db()->prepare('INSERT INTO media_bindings (media_id, person_id, btype, page, x, y, w, h, tstart, tend, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $st->execute([
        (int)$mediaId,
        (int)$personId,
        in_array($data['btype'] ?? 'region', ['region', 'page', 'time'], true) ? $data['btype'] : 'region',
        (int)($data['page'] ?? 0),
        (float)($data['x'] ?? 0),
        (float)($data['y'] ?? 0),
        (float)($data['w'] ?? 0),
        (float)($data['h'] ?? 0),
        (float)($data['tstart'] ?? 0),
        (float)($data['tend'] ?? 0),
        nowSql(),
    ]);
}

function updateBinding($id, $data)
{
    $st = db()->prepare('UPDATE media_bindings SET btype=?, page=?, x=?, y=?, w=?, h=?, tstart=?, tend=? WHERE id=?');
    $st->execute([
        in_array($data['btype'] ?? 'region', ['region', 'page', 'time'], true) ? $data['btype'] : 'region',
        (int)($data['page'] ?? 0),
        (float)($data['x'] ?? 0),
        (float)($data['y'] ?? 0),
        (float)($data['w'] ?? 0),
        (float)($data['h'] ?? 0),
        (float)($data['tstart'] ?? 0),
        (float)($data['tend'] ?? 0),
        (int)$id,
    ]);
}

function deleteBinding($id)
{
    db()->prepare('DELETE FROM media_bindings WHERE id=?')->execute([(int)$id]);
}

function getBinding($id)
{
    $st = db()->prepare('SELECT * FROM media_bindings WHERE id=?');
    $st->execute([(int)$id]);
    return $st->fetch();
}

function bindingsOfMedia($mediaId)
{
    $st = db()->prepare('SELECT b.*, p.name AS person_name, p.tree_id AS person_tree, p.is_public
        FROM media_bindings b JOIN persons p ON p.id = b.person_id
        WHERE b.media_id = ? ORDER BY b.person_id, b.id');
    $st->execute([(int)$mediaId]);
    return $st->fetchAll();
}

function bindingsOfPerson($personId)
{
    $st = db()->prepare('SELECT b.*, m.*, m.id AS media_id FROM media_bindings b JOIN media m ON m.id = b.media_id
        WHERE b.person_id = ? ORDER BY m.archival DESC, b.id DESC');
    $st->execute([(int)$personId]);
    return $st->fetchAll();
}

function formatDuration($sec)
{
    $sec = max(0, (int)round($sec));
    $h = intdiv($sec, 3600);
    $m = intdiv($sec % 3600, 60);
    $s = $sec % 60;
    return ($h ? $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT) . ':' : $m . ':') . str_pad($s, 2, '0', STR_PAD_LEFT);
}

function mediaKindIcon($kind)
{
    return [
        'image' => 'bi-image',
        'video' => 'bi-film',
        'audio' => 'bi-music-note-beamed',
        'pdf' => 'bi-file-earmark-pdf',
        'note' => 'bi-file-earmark-text',
    ][$kind] ?? 'bi-paperclip';
}

function mediaKindLabel($kind)
{
    return [
        'image' => 'Изображение',
        'video' => 'Видео',
        'audio' => 'Аудио',
        'pdf' => 'PDF-документ',
        'note' => 'Текстовая запись',
    ][$kind] ?? $kind;
}

function bindingTypeBadge($b)
{
    return ['region' => 'Область', 'page' => 'Страница PDF', 'time' => 'Таймкод'][$b['btype']] ?? 'Область';
}

function bindingGeomText($b)
{
    if ($b['btype'] === 'time') {
        return $b['tend'] > 0 ? formatDuration($b['tstart']) . ' – ' . formatDuration($b['tend']) : 'время не указано';
    }
    if ($b['btype'] === 'page') {
        $ok = $b['w'] > 0 && $b['h'] > 0;
        return 'Стр. ' . ((int)$b['page'] + 1) . ($ok ? ', область выделена' : ', область не выделена');
    }
    return $b['w'] > 0 && $b['h'] > 0 ? 'Область выделена' : 'Область не выделена';
}

function bindingSummary($b)
{
    $kind = $b['kind'] ?? '';
    $btype = $b['btype'] ?? 'region';
    if ($btype === 'time') {
        return 'Таймкод ' . formatDuration($b['tstart'] ?? 0) . ' – ' . formatDuration($b['tend'] ?? 0);
    }
    if ($btype === 'page') {
        return 'PDF · стр. ' . ((int)($b['page'] ?? 0) + 1) . ', выделена область';
    }
    return 'Выделена область на ' . strtolower(mediaKindLabel($kind));
}

