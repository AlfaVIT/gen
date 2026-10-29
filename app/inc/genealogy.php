<?php

function createTree($ownerId, $title, $description, $isPublic)
{
    $st = db()->prepare('INSERT INTO trees (owner_id, title, description, is_public, created_at, updated_at) VALUES (?,?,?,?,?,?)');
    $st->execute([(int)$ownerId, $title, $description, $isPublic ? 1 : 0, nowSql(), nowSql()]);
    return (int)db()->lastInsertId();
}

function updateTree($id, $title, $description, $isPublic)
{
    $st = db()->prepare('UPDATE trees SET title = ?, description = ?, is_public = ?, updated_at = ? WHERE id = ?');
    $st->execute([$title, $description, $isPublic ? 1 : 0, nowSql(), (int)$id]);
}

function listTreesFor($user)
{
    if ($user['role'] === 'admin') {
        $rows = db()->query('SELECT t.*, u.name AS owner_name, u.email AS owner_email,
            (SELECT COUNT(*) FROM persons p WHERE p.tree_id = t.id) AS persons_count
            FROM trees t JOIN users u ON u.id = t.owner_id ORDER BY t.updated_at DESC')->fetchAll();
        return $rows;
    }
    $st = db()->prepare('SELECT t.*, u.name AS owner_name, u.email AS owner_email,
        (SELECT COUNT(*) FROM persons p WHERE p.tree_id = t.id) AS persons_count
        FROM trees t JOIN users u ON u.id = t.owner_id
        WHERE t.owner_id = ? OR t.is_public = 1
        ORDER BY (t.owner_id = ?) DESC, t.updated_at DESC');
    $st->execute([(int)$user['id'], (int)$user['id']]);
    return $st->fetchAll();
}

function deleteTree($id)
{
    $rows = db()->prepare('SELECT file FROM media WHERE tree_id = ?')->execute([(int)$id]);
    $rows = db()->prepare('SELECT file FROM media WHERE tree_id = ?');
    $rows->execute([(int)$id]);
    foreach ($rows->fetchAll() as $m) {
        if ($m['file']) deleteMediaFile($m['file']);
    }
    $dir = uploadsDirReal() . '/t' . (int)$id;
    if (is_dir($dir)) {
        foreach (glob($dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($dir);
    }
    $st = db()->prepare('DELETE FROM trees WHERE id = ?');
    $st->execute([(int)$id]);
}

function listPersons($treeId)
{
    $st = db()->prepare('SELECT * FROM persons WHERE tree_id = ? ORDER BY name');
    $st->execute([(int)$treeId]);
    return $st->fetchAll();
}

function getPerson($id)
{
    $st = db()->prepare('SELECT * FROM persons WHERE id = ?');
    $st->execute([(int)$id]);
    return $st->fetch();
}

function createPerson($treeId, $data)
{
    $name = trim($data['name'] ?? '');
    if ($name === '') {
        throw new RuntimeException('Укажите имя персоны.');
    }
    $st = db()->prepare('INSERT INTO persons (tree_id, name, gender, birth_date, birth_place, death_date, death_place, is_public, description, social, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $st->execute([
        (int)$treeId,
        $name,
        in_array($data['gender'] ?? 'u', ['m', 'f', 'u'], true) ? $data['gender'] : 'u',
        trim($data['birth_date'] ?? ''),
        trim($data['birth_place'] ?? ''),
        trim($data['death_date'] ?? ''),
        trim($data['death_place'] ?? ''),
        !empty($data['is_public']) ? 1 : 0,
        trim($data['description'] ?? ''),
        json_encode(normalizeSocial($data['social'] ?? []), JSON_UNESCAPED_UNICODE),
        nowSql(),
        nowSql(),
    ]);
    $id = (int)db()->lastInsertId();
    touchTree($treeId);
    return $id;
}

function updatePerson($id, $data)
{
    $p = getPerson($id);
    if (!$p) {
        throw new RuntimeException('Персона не найдена.');
    }
    $name = trim($data['name'] ?? '');
    if ($name === '') {
        throw new RuntimeException('Укажите имя персоны.');
    }
    $st = db()->prepare('UPDATE persons SET name=?, gender=?, birth_date=?, birth_place=?, death_date=?, death_place=?, is_public=?, description=?, social=?, updated_at=? WHERE id=?');
    $st->execute([
        $name,
        in_array($data['gender'] ?? 'u', ['m', 'f', 'u'], true) ? $data['gender'] : 'u',
        trim($data['birth_date'] ?? ''),
        trim($data['birth_place'] ?? ''),
        trim($data['death_date'] ?? ''),
        trim($data['death_place'] ?? ''),
        !empty($data['is_public']) ? 1 : 0,
        trim($data['description'] ?? ''),
        json_encode(normalizeSocial($data['social'] ?? []), JSON_UNESCAPED_UNICODE),
        nowSql(),
        (int)$id,
    ]);
    touchTree($p['tree_id']);
}

function deletePerson($id)
{
    $p = getPerson($id);
    if (!$p) {
        return;
    }
    db()->prepare('DELETE FROM persons WHERE id = ?')->execute([(int)$id]);
    cleanupUnions($p['tree_id']);
    touchTree($p['tree_id']);
}

function normalizeSocial($raw)
{
    $out = [];
    if (is_string($raw)) {
        $dec = json_decode($raw, true);
        $raw = is_array($dec) ? $dec : [];
    }
    foreach ($raw as $row) {
        if (is_array($row)) {
            $label = trim((string)($row['label'] ?? ''));
            $url = trim((string)($row['url'] ?? ''));
        } else {
            $label = '';
            $url = trim((string)$row);
        }
        if ($url !== '') {
            if (!preg_match('~^https?://~i', $url)) {
                $url = 'https://' . $url;
            }
            $out[] = ['label' => $label, 'url' => $url];
        }
    }
    return $out;
}

function genderLabel($g)
{
    return ['m' => 'Мужской', 'f' => 'Женский', 'u' => 'Не указан'][$g] ?? 'Не указан';
}

function unionCreate($treeId, $startDate, $endDate, $note)
{
    $st = db()->prepare('INSERT INTO unions (tree_id, start_date, end_date, note, created_at) VALUES (?,?,?,?,?)');
    $st->execute([(int)$treeId, trim($startDate), trim($endDate), trim($note), nowSql()]);
    return (int)db()->lastInsertId();
}

function unionUpdate($id, $startDate, $endDate, $note)
{
    $st = db()->prepare('UPDATE unions SET start_date=?, end_date=?, note=? WHERE id=?');
    $st->execute([trim($startDate), trim($endDate), trim($note), (int)$id]);
}

function unionDelete($id)
{
    db()->prepare('DELETE FROM unions WHERE id=?')->execute([(int)$id]);
}

function listUnions($treeId)
{
    $st = db()->prepare('SELECT u.*,
        (SELECT COUNT(*) FROM union_children c WHERE c.union_id = u.id) AS children_count,
        (SELECT COUNT(*) FROM union_members m WHERE m.union_id = u.id) AS members_count
        FROM unions u WHERE u.tree_id = ? ORDER BY u.start_date, u.id');
    $st->execute([(int)$treeId]);
    return $st->fetchAll();
}

function getUnion($id)
{
    $st = db()->prepare('SELECT * FROM unions WHERE id=?');
    $st->execute([(int)$id]);
    return $st->fetch();
}

function unionMembers($unionId)
{
    $st = db()->prepare('SELECT p.* FROM union_members m JOIN persons p ON p.id = m.person_id WHERE m.union_id = ? ORDER BY p.name');
    $st->execute([(int)$unionId]);
    return $st->fetchAll();
}

function unionChildren($unionId)
{
    $st = db()->prepare('SELECT p.* FROM union_children c JOIN persons p ON p.id = c.person_id WHERE c.union_id = ? ORDER BY p.birth_date, p.name');
    $st->execute([(int)$unionId]);
    return $st->fetchAll();
}

function unionAddMember($unionId, $personId)
{
    $st = db()->prepare('SELECT COUNT(*) FROM union_members WHERE union_id=? AND person_id=?');
    $st->execute([(int)$unionId, (int)$personId]);
    if ((int)$st->fetchColumn() > 0) {
        return;
    }
    $st = db()->prepare('SELECT COUNT(*) FROM union_members WHERE union_id=?');
    $st->execute([(int)$unionId]);
    if ((int)$st->fetchColumn() >= 2) {
        throw new RuntimeException('В одном семейном союзе не может быть более двух супругов. Для нового брака создайте отдельный союз.');
    }
    $st = db()->prepare('INSERT INTO union_members (union_id, person_id) VALUES (?,?)');
    $st->execute([(int)$unionId, (int)$personId]);
}

function unionRemoveMember($unionId, $personId)
{
    db()->prepare('DELETE FROM union_members WHERE union_id=? AND person_id=?')->execute([(int)$unionId, (int)$personId]);
    cleanupUnionsByUnion($unionId);
}

function unionAddChild($unionId, $personId)
{
    $st = db()->prepare('INSERT OR IGNORE INTO union_children (union_id, person_id) VALUES (?,?)');
    $st->execute([(int)$unionId, (int)$personId]);
}

function unionRemoveChild($unionId, $personId)
{
    db()->prepare('DELETE FROM union_children WHERE union_id=? AND person_id=?')->execute([(int)$unionId, (int)$personId]);
    cleanupUnionsByUnion($unionId);
}

function cleanupUnionsByUnion($unionId)
{
    $u = getUnion($unionId);
    if (!$u) return;
    $m = (int)db()->query('SELECT COUNT(*) FROM union_members WHERE union_id=' . (int)$unionId)->fetchColumn();
    $c = (int)db()->query('SELECT COUNT(*) FROM union_children WHERE union_id=' . (int)$unionId)->fetchColumn();
    if ($m === 0 && $c === 0) {
        unionDelete($unionId);
    }
}

function cleanupUnions($treeId)
{
    db()->prepare('DELETE FROM unions WHERE tree_id=? AND id NOT IN
        (SELECT union_id FROM union_members UNION SELECT union_id FROM union_children)')->execute([(int)$treeId]);
}

function findUnionOfPersons($treeId, array $personIds)
{
    $ids = array_values(array_unique(array_map('intval', $personIds)));
    sort($ids);
    if (!$ids) return null;
    foreach (listUnions($treeId) as $u) {
        $members = array_column(unionMembers($u['id']), 'id');
        $memberIds = array_map('intval', $members);
        sort($memberIds);
        if ($memberIds === $ids) {
            return $u;
        }
    }
    return null;
}

function personUnions($personId)
{
    $st = db()->prepare('SELECT DISTINCT u.* FROM unions u
        JOIN union_members m ON m.union_id = u.id WHERE m.person_id = ? ORDER BY u.start_date, u.id');
    $st->execute([(int)$personId]);
    return $st->fetchAll();
}

function personChildUnions($personId)
{
    $st = db()->prepare('SELECT DISTINCT u.* FROM unions u
        JOIN union_children c ON c.union_id = u.id WHERE c.person_id = ? ORDER BY u.start_date, u.id');
    $st->execute([(int)$personId]);
    return $st->fetchAll();
}

function personParents($personId)
{
    $out = [];
    foreach (personChildUnions($personId) as $u) {
        foreach (unionMembers($u['id']) as $m) {
            $m['_union'] = $u;
            $out[] = $m;
        }
    }
    return $out;
}

function personSpouses($personId)
{
    $out = [];
    foreach (personUnions($personId) as $u) {
        foreach (unionMembers($u['id']) as $m) {
            if ((int)$m['id'] === (int)$personId) continue;
            $m['_union'] = $u;
            $out[] = $m;
        }
    }
    return $out;
}

function personChildren($personId)
{
    $out = [];
    foreach (personUnions($personId) as $u) {
        foreach (unionChildren($u['id']) as $c) {
            $c['_union'] = $u;
            $out[] = $c;
        }
    }
    return $out;
}

function allDescendants($treeId)
{
    $children = [];
    foreach (listUnions($treeId) as $u) {
        $members = unionMembers($u['id']);
        $kids = unionChildren($u['id']);
        foreach ($members as $m) {
            foreach ($kids as $k) {
                $children[(int)$m['id']][(int)$k['id']] = true;
            }
        }
    }
    $desc = [];
    foreach ($children as $pid => $set) {
        $queue = array_keys($set);
        while ($queue) {
            $cur = array_shift($queue);
            $desc[$pid][$cur] = true;
            foreach (array_keys($children[$cur] ?? []) as $next) {
                if (!isset($desc[$pid][$next])) {
                    $desc[$pid][$next] = true;
                    $queue[] = $next;
                }
            }
        }
    }
    return $desc;
}

function isAncestor($treeId, $ancestorId, $personId)
{
    if ((int)$ancestorId === (int)$personId) return true;
    $desc = allDescendants($treeId);
    return isset($desc[(int)$ancestorId][(int)$personId]);
}

function attachParents($childId, array $parentIds)
{
    $child = getPerson($childId);
    $treeId = (int)$child['tree_id'];
    $parentIds = array_values(array_unique(array_map('intval', $parentIds)));
    $parentIds = array_filter($parentIds, fn($id) => $id !== (int)$childId);
    if (!$parentIds) {
        return;
    }
    foreach ($parentIds as $pid) {
        $pp = getPerson($pid);
        if (!$pp || (int)$pp['tree_id'] !== $treeId) {
            throw new RuntimeException('Родитель вне дерева.');
        }
        if (isAncestor($treeId, $pid, (int)$childId)) {
            $st = db()->prepare('SELECT name FROM persons WHERE id=?');
            $st->execute([$pid]);
            throw new RuntimeException('Нельзя: «' . $st->fetchColumn() . '» уже является потомком персоны.');
        }
    }
    if (count($parentIds) === 2 && $parentIds[0] === $parentIds[1]) {
        $parentIds = [$parentIds[0]];
    }
    $union = findUnionOfPersons($treeId, $parentIds);
    if (!$union) {
        $unionId = unionCreate($treeId, '', '', '');
        foreach ($parentIds as $pid) {
            unionAddMember($unionId, $pid);
        }
        $union = getUnion($unionId);
    }
    unionAddChild((int)$union['id'], (int)$childId);
}

function setChildUnion($childId, $unionId, $add)
{
    if ($add) {
        unionAddChild((int)$unionId, (int)$childId);
    } else {
        unionRemoveChild((int)$unionId, (int)$childId);
    }
}

function spouseUnionBetween($treeId, $aId, $bId)
{
    foreach (personUnions($aId) as $u) {
        foreach (unionMembers($u['id']) as $m) {
            if ((int)$m['id'] === (int)$bId) {
                return $u;
            }
        }
    }
    return null;
}

function touchTree($treeId)
{
    db()->prepare('UPDATE trees SET updated_at = ? WHERE id = ?')->execute([nowSql(), (int)$treeId]);
}

function treePersonsIndex($treeId)
{
    $idx = [];
    foreach (listPersons($treeId) as $p) {
        $idx[(int)$p['id']] = $p;
    }
    return $idx;
}

function yearFromDate($str)
{
    if (is_string($str) && preg_match('/(18|19|20)\d{2}/', $str, $m)) {
        return (int)$m[0];
    }
    return null;
}

function personAge($person)
{
    $by = yearFromDate($person['birth_date'] ?? '');
    if ($by === null) {
        return null;
    }
    $dy = null;
    if (!empty($person['death_date'])) {
        $dy = yearFromDate($person['death_date']);
    }
    $ref = $dy !== null ? $dy : (int)date('Y');
    $age = $ref - $by;
    if ($age < 0 || $age > 150) {
        return null;
    }
    return $age;
}

function pluralYears($n)
{
    $n = abs((int)$n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) {
        return 'лет';
    }
    if ($n1 === 1) {
        return 'год';
    }
    if ($n1 >= 2 && $n1 <= 4) {
        return 'года';
    }
    return 'лет';
}
function deleteAllUserTrees($userId)
{
    $st = db()->prepare('SELECT id FROM trees WHERE owner_id = ?');
    $st->execute([(int)$userId]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $tid) {
        deleteTree($tid);
    }
}

function countUserTrees($userId)
{
    $st = db()->prepare('SELECT COUNT(*) FROM trees WHERE owner_id = ?');
    $st->execute([(int)$userId]);
    return (int)$st->fetchColumn();
}
