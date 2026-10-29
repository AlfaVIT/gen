<?php

function xmlH($s)
{
    return is_string($s) ? trim($s) : '';
}

function collectBindingsData($mediaId)
{
    $st = db()->prepare('SELECT id, person_id, btype, page, x, y, w, h, tstart, tend FROM media_bindings WHERE media_id=? ORDER BY id');
    $st->execute([(int)$mediaId]);
    return $st->fetchAll();
}

function exportTreeXml($treeId, $embed = true)
{
    $tree = loadTree($treeId);
    if (!$tree) {
        throw new RuntimeException('Дерево не найдено.');
    }
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->formatOutput = true;
    $root = $doc->createElement('genealogydata');
    $root->setAttribute('version', '1.0');
    $root->setAttribute('export_date', nowSql());
    $root->setAttribute('site', cfg('site_name'));
    $doc->appendChild($root);

    $tEl = $doc->createElement('tree');
    $tEl->setAttribute('id', (string)$tree['id']);
    $tEl->setAttribute('title', xmlH($tree['title']));
    $tEl->setAttribute('description', xmlH($tree['description']));
    $tEl->setAttribute('is_public', (string)(int)$tree['is_public']);
    $root->appendChild($tEl);

    $personsEl = $doc->createElement('persons');
    foreach (listPersons($tree['id']) as $p) {
        $el = $doc->createElement('person');
        $el->setAttribute('id', (string)$p['id']);
        $el->setAttribute('name', xmlH($p['name']));
        $el->setAttribute('gender', $p['gender']);
        $el->setAttribute('birth_date', xmlH($p['birth_date']));
        $el->setAttribute('birth_place', xmlH($p['birth_place']));
        $el->setAttribute('death_date', xmlH($p['death_date']));
        $el->setAttribute('death_place', xmlH($p['death_place']));
        $el->setAttribute('is_public', (string)(int)$p['is_public']);
        $desc = $doc->createElement('description');
        $desc->appendChild($doc->createCDATASection((string)$p['description']));
        $el->appendChild($desc);
        $socialsEl = $doc->createElement('socials');
        foreach (json_decode($p['social'] ?: '[]', true) ?: [] as $s) {
            $sEl = $doc->createElement('social');
            $sEl->setAttribute('label', xmlH($s['label'] ?? ''));
            $sEl->setAttribute('url', xmlH($s['url'] ?? ''));
            $socialsEl->appendChild($sEl);
        }
        $el->appendChild($socialsEl);
        $personsEl->appendChild($el);
    }
    $tEl->appendChild($personsEl);

    $unionsEl = $doc->createElement('unions');
    foreach (listUnions($tree['id']) as $u) {
        $el = $doc->createElement('union');
        $el->setAttribute('id', (string)$u['id']);
        $el->setAttribute('start_date', xmlH($u['start_date']));
        $el->setAttribute('end_date', xmlH($u['end_date']));
        $el->setAttribute('note', xmlH($u['note']));
        foreach (unionMembers($u['id']) as $m) {
            $mEl = $doc->createElement('member');
            $mEl->setAttribute('person', (string)$m['id']);
            $el->appendChild($mEl);
        }
        foreach (unionChildren($u['id']) as $c) {
            $cEl = $doc->createElement('child');
            $cEl->setAttribute('person', (string)$c['id']);
            $el->appendChild($cEl);
        }
        $unionsEl->appendChild($el);
    }
    $tEl->appendChild($unionsEl);

    $mediaEl = $doc->createElement('media');
    $embedMax = (int)cfg('embed_base64_max');
    foreach (listTreeMedia($tree['id']) as $m) {
        $el = $doc->createElement('item');
        $el->setAttribute('kind', $m['kind']);
        $el->setAttribute('archival', (string)(int)$m['archival']);
        $el->setAttribute('title', xmlH($m['title']));
        $el->setAttribute('original', xmlH($m['original']));
        $el->setAttribute('mime', xmlH($m['mime']));
        $el->setAttribute('url', xmlH($m['url']));
        $el->setAttribute('file', xmlH($m['file']));
        $el->setAttribute('size', (string)(int)$m['size']);
        $note = $doc->createElement('note');
        $note->appendChild($doc->createCDATASection((string)$m['note']));
        $el->appendChild($note);
        if ($m['file'] && $embed && $m['size'] <= $embedMax) {
            $real = mediaFileReal($m['file']);
            if ($real && is_file($real)) {
                $data = $doc->createElement('data');
                $data->setAttribute('encoding', 'base64');
                $data->appendChild($doc->createCDATASection(base64_encode(file_get_contents($real))));
                $el->appendChild($data);
            }
        }
        $bEl = $doc->createElement('bindings');
        foreach (collectBindingsData($m['id']) as $b) {
            $bind = $doc->createElement('binding');
            $bind->setAttribute('person', (string)$b['person_id']);
            $bind->setAttribute('btype', $b['btype']);
            $bind->setAttribute('page', (string)(int)$b['page']);
            $bind->setAttribute('x', sprintf('%F', $b['x']));
            $bind->setAttribute('y', sprintf('%F', $b['y']));
            $bind->setAttribute('w', sprintf('%F', $b['w']));
            $bind->setAttribute('h', sprintf('%F', $b['h']));
            $bind->setAttribute('tstart', sprintf('%F', $b['tstart']));
            $bind->setAttribute('tend', sprintf('%F', $b['tend']));
            $bEl->appendChild($bind);
        }
        $el->appendChild($bEl);
        $mediaEl->appendChild($el);
    }
    $tEl->appendChild($mediaEl);

    return $doc->saveXML();
}

function importTreeFromXml($ownerId, $xmlString)
{
    $prev = libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $ok = $doc->loadXML($xmlString);
    if (!$ok) {
        libxml_use_internal_errors($prev);
        throw new RuntimeException('Некорректный XML: ' . trim(libxml_get_errors()[0]->message ?? 'неизвестная ошибка'));
    }
    libxml_use_internal_errors($prev);

    $root = $doc->documentElement;
    $tEl = null;
    foreach ($root->getElementsByTagName('tree') as $n) {
        $tEl = $n;
        break;
    }
    if (!$tEl) {
        throw new RuntimeException('В XML не найдено дерево.');
    }
    $title = trim($tEl->getAttribute('title'));
    if ($title === '') {
        $title = 'Импортированное дерево';
    }
    $treeId = createTree($ownerId, $title, trim($tEl->getAttribute('description')), (int)$tEl->getAttribute('is_public') === 1);

    $pmap = [];
    $personsEl = null;
    foreach ($tEl->getElementsByTagName('persons') as $n) {
        $personsEl = $n;
        break;
    }
    if ($personsEl) {
        foreach ($personsEl->getElementsByTagName('person') as $p) {
            $desc = '';
            foreach ($p->getElementsByTagName('description') as $d) {
                $desc = $d->textContent;
                break;
            }
            $socials = [];
            foreach ($p->getElementsByTagName('social') as $s) {
                $socials[] = ['label' => $s->getAttribute('label'), 'url' => $s->getAttribute('url')];
            }
            $gender = in_array($p->getAttribute('gender'), ['m', 'f', 'u'], true) ? $p->getAttribute('gender') : 'u';
            $newId = createPerson($treeId, [
                'name' => trim($p->getAttribute('name')) !== '' ? trim($p->getAttribute('name')) : 'Персона',
                'gender' => $gender,
                'birth_date' => $p->getAttribute('birth_date'),
                'birth_place' => $p->getAttribute('birth_place'),
                'death_date' => $p->getAttribute('death_date'),
                'death_place' => $p->getAttribute('death_place'),
                'is_public' => (int)$p->getAttribute('is_public') === 1,
                'description' => $desc,
                'social' => $socials,
            ]);
            $pmap[(int)$p->getAttribute('id')] = $newId;
        }
    }

    foreach ($tEl->getElementsByTagName('union') as $u) {
        $uid = unionCreate($treeId, $u->getAttribute('start_date'), $u->getAttribute('end_date'), $u->getAttribute('note'));
        foreach ($u->getElementsByTagName('member') as $m) {
            $pid = (int)$m->getAttribute('person');
            if (isset($pmap[$pid])) {
                unionAddMember($uid, $pmap[$pid]);
            }
        }
        foreach ($u->getElementsByTagName('child') as $c) {
            $pid = (int)$c->getAttribute('person');
            if (isset($pmap[$pid])) {
                unionAddChild($uid, $pmap[$pid]);
            }
        }
    }

    $mediaEl = null;
    foreach ($tEl->getElementsByTagName('media') as $n) {
        $mediaEl = $n;
        break;
    }
    if ($mediaEl) {
        $treeDir = ensureTreeUploadDir($treeId);
        foreach ($mediaEl->getElementsByTagName('item') as $item) {
            $kind = $item->getAttribute('kind');
            $fileMeta = null;
            $dataEl = null;
            foreach ($item->getElementsByTagName('data') as $d) {
                $dataEl = $d;
                break;
            }
            if ($kind !== 'note' && $dataEl) {
                $bytes = base64_decode(trim($dataEl->textContent));
                if ($bytes === false || $bytes === '') {
                    throw new RuntimeException('Ошибка чтения вложенных данных материала.');
                }
                $mime = $item->getAttribute('mime');
                $allowed = cfg('allowed_mime');
                if ($mime && isset($allowed[$mime])) {
                    $ext = $allowed[$mime];
                } else {
                    $orig = $item->getAttribute('original');
                    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                    $mime = '';
                    foreach ($allowed as $mm => $ex) {
                        if ($ex === $ext) {
                            $mime = $mm;
                            break;
                        }
                    }
                    if ($mime === '') {
                        continue;
                    }
                }
                $name = bin2hex(random_bytes(12)) . '.' . $ext;
                if (file_put_contents($treeDir . '/' . $name, $bytes) === false) {
                    continue;
                }
                $fileMeta = [
                    'file' => 'uploads/t' . $treeId . '/' . $name,
                    'original' => $item->getAttribute('original'),
                    'mime' => $mime,
                    'size' => strlen($bytes),
                ];
            }
            $note = '';
            foreach ($item->getElementsByTagName('note') as $nn) {
                $note = $nn->textContent;
                break;
            }
            $mid = createMedia($treeId, $ownerId, $kind, [
                'title' => $item->getAttribute('title'),
                'note' => $note,
                'archival' => (int)$item->getAttribute('archival') === 1,
                'url' => $item->getAttribute('url'),
            ], $fileMeta);
            $bEl = null;
            foreach ($item->getElementsByTagName('bindings') as $bn) {
                $bEl = $bn;
                break;
            }
            if ($bEl) {
                foreach ($bEl->getElementsByTagName('binding') as $bind) {
                    $oldPerson = (int)$bind->getAttribute('person');
                    if (!isset($pmap[$oldPerson])) continue;
                    addBinding($mid, $pmap[$oldPerson], [
                        'btype' => $bind->getAttribute('btype') ?: 'region',
                        'page' => (int)$bind->getAttribute('page'),
                        'x' => (float)$bind->getAttribute('x'),
                        'y' => (float)$bind->getAttribute('y'),
                        'w' => (float)$bind->getAttribute('w'),
                        'h' => (float)$bind->getAttribute('h'),
                        'tstart' => (float)$bind->getAttribute('tstart'),
                        'tend' => (float)$bind->getAttribute('tend'),
                    ]);
                }
            }
        }
    }

    return $treeId;
}
