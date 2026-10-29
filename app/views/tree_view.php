<?php
$personsArr = [];
foreach ($apiPersons as $p) {
    $personsArr[] = [
        'id' => (int)$p['id'],
        'name' => (int)$p['ghost'] === 1 ? '' : $p['name'],
        'ghost' => (int)$p['ghost'] === 1,
        'gender' => $p['gender'],
        'birth' => (int)$p['ghost'] === 1 ? '' : $p['birth_date'],
        'death' => (int)$p['ghost'] === 1 ? '' : $p['death_date'],
        'avatar' => (int)$p['ghost'] === 1 ? '' : ($p['avatar'] ?? ''),
    ];
}
$focusId = (int)getStr('focus');
$initJson = json_encode([
    'tree' => ['id' => (int)$tree['id'], 'title' => $tree['title']],
    'canEdit' => $canEdit ? 1 : 0,
    'persons' => $personsArr,
    'unions' => $apiUnions,
    'csrf' => csrfToken(),
    'focus' => $focusId,
], JSON_UNESCAPED_UNICODE);
?>
<div class="container-xl py-4">
    <nav class="small mb-3 text-secondary"><a href="<?= e(u('index.php?p=trees')) ?>">Деревья</a> <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> <?= e($tree['title']) ?></nav>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="tree-ico"><img src="<?= e(basePath()) ?>/assets/img/tree-logo.svg" alt="" width="100%" height="100%"></div>
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="page-title mb-0 fs-2"><?= e($tree['title']) ?></h1>
                    <?php if ((int)$tree['is_public'] === 1): ?><span class="badge badge-soft"><i class="bi bi-globe2 me-1"></i>публичное</span>
                    <?php else: ?><span class="badge-owner badge"><i class="bi bi-lock me-1"></i>личное</span><?php endif; ?>
                </div>
                <div class="text-secondary small mt-1">
                    <?php if ($isOwner): ?><i class="bi bi-person me-1"></i><?= e($tree['owner_name'] ?: $tree['owner_email']) ?><?php else: ?>владелец: <?= e($tree['owner_name'] ?: $tree['owner_email']) ?><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($canEdit): ?>
                <a class="btn btn-primary" href="<?= e(u('index.php?p=person_new&tree=' . (int)$tree['id'])) ?>"><i class="bi bi-person-plus me-1"></i>Добавить персону</a>
            <?php endif; ?>
            <?php if ($isOwner): ?>
                <a class="btn btn-outline-secondary" href="<?= e(u('index.php?p=export&id=' . (int)$tree['id'])) ?>" title="Скачать дерево в XML со всеми данными"><i class="bi bi-download me-1"></i>Скачать XML</a>
                <a class="btn btn-outline-secondary" href="<?= e(u('index.php?p=tree_settings&id=' . (int)$tree['id'])) ?>"><i class="bi bi-gear me-1"></i>Настройки</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6"><div class="card p-3 text-center"><div class="stat-chip-num fs-4 fw-bold" id="statPersons" style="color:var(--accent)"><?= (int)$stats['persons'] ?></div><div class="small text-secondary">персон</div></div></div>
        <div class="col-md-3 col-6"><div class="card p-3 text-center"><div class="fs-4 fw-bold" id="statUnions" style="color:var(--accent)"><?= (int)$stats['unions'] ?></div><div class="small text-secondary">семейных союзов</div></div></div>
        <div class="col-md-3 col-6"><div class="card p-3 text-center"><div class="fs-4 fw-bold" style="color:var(--accent)"><?= (int)$stats['media'] ?></div><div class="small text-secondary">материалов</div></div></div>
        <div class="col-md-3 col-6"><div class="card p-3 text-center"><div class="fs-4 fw-bold" id="statStructure" style="color:var(--accent)"><?= count($personsArr) ?></div><div class="small text-secondary">в структуре дерева</div></div></div>
    </div>

    <?php if (!$canEdit && (int)$tree['is_public'] === 1 && $user['role'] !== 'admin'): ?>
        <div class="alert alert-warning small mb-4"><i class="bi bi-eye-slash me-2"></i>Вы просматриваете дерево в режиме «Гость». Скрытые персоны и их потомки отображаются как «Персона» и доступны только владельцу.</div>
    <?php endif; ?>
    <?php if ($canEdit && (int)$user['consent'] !== 1): ?>
        <div class="alert alert-warning small mb-4"><i class="bi bi-shield-exclamation me-2"></i>Чтобы вносить изменения, дайте согласие на обработку персональных данных: <a href="<?= e(u('index.php?p=consent')) ?>">перейти к согласию</a>.</div>
    <?php endif; ?>

    <div class="card p-3 mb-4">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <button class="btn btn-sm btn-outline-secondary" id="zoomOut" title="Уменьшить"><i class="bi bi-dash-lg"></i></button>
            <span class="btn btn-sm btn-light" id="zoomLevel" style="min-width:52px">100%</span>
            <button class="btn btn-sm btn-outline-secondary" id="zoomIn" title="Увеличить"><i class="bi bi-plus-lg"></i></button>
            <button class="btn btn-sm btn-outline-secondary" id="zoomFit" title="Поместить всё в окно"><i class="bi bi-arrows-fullscreen"></i></button>
            <button class="btn btn-sm btn-outline-secondary" id="treeFullscreen" title="Развернуть на весь экран"><i class="bi bi-fullscreen"></i></button>
            <a class="btn btn-sm btn-outline-secondary ms-auto" href="<?= e(u('index.php?p=archive&tree=' . (int)$tree['id'])) ?>"><i class="bi bi-archive me-1"></i>Архивные данные дерева</a>
        </div>
        <div class="tree-wrap" id="treeWrap">
            <svg id="treeSvg" role="img" aria-label="Схема дерева"></svg>
            <button class="fs-exit" id="fsExit" type="button" title="Выйти из полноэкранного режима (Esc)"><i class="bi bi-fullscreen-exit me-1"></i>Выйти</button>
            <div class="tree-hint">
                <span><i class="bi bi-hand-index-thumb me-1"></i>перетаскивание</span>
                <span><i class="bi bi-zoom-in me-1"></i>колесо мыши</span>
                <span><i class="bi bi-cursor me-1"></i>клик по карточке — перестроить родню</span>
                <span><i class="bi bi-person me-1"></i>клик по имени — карточка персоны</span>
            </div>
        </div>
    </div>

    <?php if ($tree['description']): ?>
        <div class="card p-4 mb-4"><div class="section-label mb-2">Описание</div><p class="mb-0"><?= nl2br(e($tree['description'])) ?></p></div>
    <?php endif; ?>
</div>

<script>
    window.TREE_INIT = <?= $initJson ?>;
    window.TREE_BASE = '<?= e(basePath()) ?>/';
    window.TREE_FOCUS = <?= (int)$focusId ?>;
</script>

<button class="d-none" id="quickAddBtn" data-bs-toggle="modal" data-bs-target="#quickAddModal"></button>
<div class="modal fade" id="quickAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="quickAddTitle">Добавить</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form id="quickAddForm">
                    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="tree" value="<?= (int)$tree['id'] ?>">
                    <input type="hidden" name="person" id="quickAddPerson">
                    <input type="hidden" name="relation" id="quickAddRelation">
                    <div class="mb-3">
                        <label class="form-label">Имя <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="quickAddName" required maxlength="200" placeholder="Например: Иван Иванов">
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Пол</label>
                            <select class="form-select" name="gender">
                                <option value="u">Не указан</option>
                                <option value="m">Мужской</option>
                                <option value="f">Женский</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Дата рождения</label>
                            <input type="text" class="form-control" name="birth" placeholder="необязательно">
                        </div>
                    </div>
                    <div class="small text-secondary mt-3 mb-0" id="quickAddHint"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" form="quickAddForm" class="btn btn-primary" id="quickAddSubmit">Добавить</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('quickAddModal');
        var relLabels = { parent: 'Добавить родителя (предка)', child: 'Добавить ребёнка (потомка)', spouse: 'Добавить супруга(у)' };
        var relHints = {
            parent: 'Новая персона станет ближайшим предком: мужской род — отец, женский — мать.',
            child: 'Новая персона станет потомком выбранной персоны (добавится в текущий союз).',
            spouse: 'Новая персона станет супругом(ой) и появится рядом.'
        };
        window.TreeQuickAdd = function (relation, personId) {
            var t = document.getElementById('quickAddTitle');
            var r = document.getElementById('quickAddRelation');
            var p = document.getElementById('quickAddPerson');
            var n = document.getElementById('quickAddName');
            var h = document.getElementById('quickAddHint');
            if (t) t.textContent = relLabels[relation] || relLabels.spouse;
            if (r) r.value = relation;
            if (p) p.value = personId;
            if (n) n.value = '';
            if (h) h.textContent = relHints[relation] || '';
            document.getElementById('quickAddBtn').click();
            setTimeout(function () { if (n) n.focus(); }, 120);
        };
        document.getElementById('quickAddForm').addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = document.getElementById('quickAddSubmit');
            btn.disabled = true; btn.textContent = 'Добавление…';
            $.ajax({
                url: window.TREE_BASE + 'index.php?p=api_rel_add',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json'
            }).done(function (resp) {
                if (resp && resp.ok) {
                    if (window.TreeReload) window.TreeReload(resp);
                    var d = resp.data || {};
                    if (d.persons) {
                        var sp = document.getElementById('statPersons');
                        if (sp) sp.textContent = d.persons.length;
                        var ss = document.getElementById('statStructure');
                        if (ss) ss.textContent = d.persons.length;
                    }
                    if (d.unions) {
                        var su = document.getElementById('statUnions');
                        if (su) su.textContent = d.unions.length;
                    }
                    showToast('Персона добавлена');
                    $(modal).modal('hide');
                } else {
                    alert((resp && resp.error) ? resp.error : 'Не удалось добавить персону.');
                }
            }).fail(function (xhr) {
                alert('Ошибка запроса (' + xhr.status + ').');
            }).always(function () {
                btn.disabled = false; btn.textContent = 'Добавить';
            });
        });
        function showToast(msg) {
            var zone = document.querySelector('.flash-zone');
            if (!zone) { alert(msg); return; }
            var d = document.createElement('div');
            d.className = 'alert alert-success alert-dismissible fade show';
            d.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>';
            zone.appendChild(d);
            setTimeout(function () { d.remove(); }, 4000);
        }
    })();
</script>
