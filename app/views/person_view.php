<?php
function pc($p, $self = false, $ghost = false)
{
    if ($ghost) {
        return '<span class="person-chip p-ghost"><span class="p-ava"><i class="bi bi-eye-slash"></i></span><span>Персона</span></span>';
    }
    $ava = !empty($p['avatar'])
        ? '<span class="p-ava"><img src="' . e(mediaViewUrl($p['avatar'])) . '" alt=""></span>'
        : '<span class="p-ava"><img src="' . e(basePath()) . '/assets/img/avatar-' . ($p['gender'] === 'm' ? 'm' : ($p['gender'] === 'f' ? 'f' : 'u')) . '.svg" alt=""></span>';
    $link = '<a class="person-chip p-gender-' . e($p['gender']) . ' text-decoration-none' . ($self ? ' border border-primary' : '') . '" href="' . e(u('index.php?p=person&id=' . (int)$p['id'])) . '">'
        . $ava . '<span>' . e($p['name']) . '</span></a>';
    return $link;
}
?>
<div class="container-xl py-4">
    <?php if ($ghost): ?>
        <div class="card p-5 text-center">
            <div class="auth-logo"><i class="bi bi-eye-slash"></i></div>
            <h1 class="h4 fw-bold mb-2">Скрытая персона</h1>
            <p class="text-secondary">Эта персона отмечена владельцем как скрытая. Её данные и данные её потомков доступны только владельцу дерева и администратору. В структуре дерева она отображается как «Персона».</p>
            <a class="btn btn-primary mx-auto" href="<?= e(u('index.php?p=tree&id=' . (int)$tree['id'] . '&focus=' . (int)$person['id'])) ?>">Вернуться к дереву</a>
        </div>
    <?php else: ?>
        <nav class="small mb-3 text-secondary"><a href="<?= e(u('index.php?p=trees')) ?>">Деревья</a> <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> <a href="<?= e(u('index.php?p=tree&id=' . (int)$tree['id'] . '&focus=' . (int)$person['id'])) ?>"><?= e($tree['title']) ?></a> <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> <?= e($person['name']) ?></nav>

        <div class="card p-4 p-lg-5 mb-4">
            <div class="row g-4 align-items-start">
                <div class="col-auto">
                    <?php if ($person['avatar'] !== ''): ?>
                        <a href="#" id="avatarOpen" title="Открыть фото">
                            <span class="avatar-lg avatar-photo"><img src="<?= e(mediaViewUrl($person['avatar'])) ?>" alt=""></span>
                        </a>
                    <?php else: ?>
                        <span class="avatar-lg"><img src="<?= e(basePath()) ?>/assets/img/avatar-<?= $person['gender'] === 'm' ? 'm' : ($person['gender'] === 'f' ? 'f' : 'u') ?>.svg" alt=""></span>
                    <?php endif; ?>
                </div>
                <div class="col">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h1 class="person-hero-name fs-2 mb-0"><?= e($person['name']) ?></h1>
                        <span class="badge badge-soft"><?= e(genderLabel($person['gender'])) ?></span>
                        <?php $ageVal = personAge($person); if ($ageVal !== null): ?>
                            <span class="badge badge-owner" title="Вычислено по году рождения<?= $person['death_date'] !== '' ? ' и смерти' : '' ?>"><i class="bi bi-hourglass-split me-1"></i><?= $person['death_date'] !== '' ? 'прожил(а) около ' : 'возраст около ' ?><?= $ageVal ?> <?= pluralYears($ageVal) ?></span>
                        <?php endif; ?>
                        <?php if ((int)$person['is_public'] !== 1): ?><span class="badge badge-owner"><i class="bi bi-eye-slash me-1"></i>скрытая персона</span><?php endif; ?>
                    </div>
                    <div class="dates-line d-flex flex-wrap gap-3">
                        <?php if ($person['birth_date'] !== '' || $person['birth_place'] !== ''): ?>
                            <span><i class="bi bi-stars me-1"></i><strong>Рождение:</strong> <?= e($person['birth_date'] !== '' ? $person['birth_date'] : '—') ?><?= $person['birth_place'] !== '' ? ' · ' . e($person['birth_place']) : '' ?></span>
                        <?php endif; ?>
                        <?php if ($person['death_date'] !== '' || $person['death_place'] !== ''): ?>
                            <span><i class="bi bi-flower1 me-1"></i><strong>Смерть:</strong> <?= e($person['death_date'] !== '' ? $person['death_date'] : '—') ?><?= $person['death_place'] !== '' ? ' · ' . e($person['death_place']) : '' ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($canEdit): ?>
                        <div class="d-flex gap-2 flex-wrap mt-3">
                            <a class="btn btn-primary btn-sm" href="<?= e(u('index.php?p=person_edit&id=' . (int)$person['id'])) ?>"><i class="bi bi-pencil me-1"></i>Редактировать</a>
                            <button class="btn btn-outline-secondary btn-sm" type="button" id="avatarEditBtn"><i class="bi bi-camera me-1"></i><?= $person['avatar'] !== '' ? 'Сменить фото' : 'Добавить фото' ?></button>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= e(u('index.php?p=media_add&tree=' . (int)$tree['id'] . '&person=' . (int)$person['id'])) ?>"><i class="bi bi-paperclip me-1"></i>Прикрепить материал</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($person['description'] !== ''): ?>
                <hr class="my-4">
                <div class="section-label mb-2">Описание</div>
                <div class="text-wrap"><?= nl2br(e($person['description'])) ?></div>
            <?php endif; ?>

            <?php if ($socials): ?>
                <hr class="my-4">
                <div class="section-label mb-3">Соцсети и ссылки</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($socials as $s): ?>
                        <a class="person-chip" target="_blank" rel="noopener" href="<?= e($s['url']) ?>"><i class="bi bi-link-45deg me-1 text-secondary"></i><?= e($s['label'] ?: $s['url']) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <div class="col-lg-7 d-flex flex-column gap-4">
                <div class="card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h5 fw-bold mb-0"><img src="<?= e(basePath()) ?>/assets/img/tree-logo.svg" alt="" width="22" height="22" class="me-2" style="vertical-align:-4px">Семья</h2>
                    </div>

                    <?php if (!$unionsMine): ?>
                        <div class="empty-state py-4"><div><i class="bi bi-people"></i></div>
                            <p class="mt-2 mb-0 small">Пока нет союзов и детей.</p>
                            <?php if ($canEdit): ?>
                                <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
                                    <button class="btn btn-soft btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#addSpouseForm"><i class="bi bi-heart me-1"></i>Добавить союз</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($unionsMine as $u):
                        $members = unionMembers($u['id']);
                        $kids = unionChildren($u['id']);
                    ?>
                        <div class="relation-card mb-3">
                            <div class="d-flex align-items-start gap-2 flex-wrap mb-2">
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        <?php if ($members): foreach ($members as $m): echo pc($m, (int)$m['id'] === (int)$person['id']); endforeach; else: ?>
                                            <span class="badge badge-owner">состав не указан</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($u['start_date'] !== '' || $u['end_date'] !== ''): ?>
                                        <span class="union-badge">
                                            <?php if ($u['start_date'] !== ''): ?><i class="bi bi-heart me-1"></i><?= e($u['start_date']) ?><?php endif; ?>
                                            <?php if ($u['end_date'] !== ''): ?> — расторгнут: <?= e($u['end_date']) ?><?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($u['note'] !== ''): ?><div class="small text-secondary mt-1"><?= e($u['note']) ?></div><?php endif; ?>
                                </div>
                                <?php if ($canEdit): ?>
                                    <div class="d-flex gap-1">
                                        <button class="iconbtn" type="button" title="Изменить даты брака" data-bs-toggle="collapse" data-bs-target="#editUnion<?= (int)$u['id'] ?>"><i class="bi bi-pencil"></i></button>
                                        <form method="post" action="<?= e(u('index.php?p=person&id=' . (int)$person['id'])) ?>" class="d-inline">
                                            <?= csrfField() ?><input type="hidden" name="act" value="union_delete">
                                            <input type="hidden" name="union_id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="confirm_text" value="УДАЛИТЬ">
                                            <button class="iconbtn danger" title="Удалить союз" data-confirm-click="Удалить этот семейный союз безвозвратно?"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($kids): ?>
                                <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                                    <i class="bi bi-arrow-down text-secondary"></i>
                                    <?php foreach ($kids as $k): echo pc($k); endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($canEdit): ?>
                                <div class="collapse mt-2" id="editUnion<?= (int)$u['id'] ?>">
                                    <form method="post" action="<?= e(u('index.php?p=person&id=' . (int)$person['id'])) ?>" class="row g-2 bg-body-tertiary p-3 rounded-3">
                                        <?= csrfField() ?><input type="hidden" name="act" value="union_save"><input type="hidden" name="union_id" value="<?= (int)$u['id'] ?>">
                                        <div class="col-md-4"><label class="form-label small">Брак заключён</label><input class="form-control form-control-sm" name="u_start" value="<?= e($u['start_date']) ?>" placeholder="дата или факт"></div>
                                        <div class="col-md-4"><label class="form-label small">Расторгнут</label><input class="form-control form-control-sm" name="u_end" value="<?= e($u['end_date']) ?>" placeholder="дата или факт"></div>
                                        <div class="col-md-4"><label class="form-label small">Примечание</label><input class="form-control form-control-sm" name="u_note" value="<?= e($u['note']) ?>"></div>
                                        <div class="col-12 d-flex gap-2"><button class="btn btn-primary btn-sm">Сохранить</button></div>
                                    </form>
                                </div>

                                <div class="d-flex flex-wrap gap-2 mt-3 border-top pt-3" style="border-color:var(--border)!important">
                                    <button class="btn btn-soft btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#childNew<?= (int)$u['id'] ?>"><i class="bi bi-person-plus me-1"></i>Добавить ребёнка</button>
                                </div>

                                <div class="collapse mt-2" id="childNew<?= (int)$u['id'] ?>">
                                    <div class="row g-2 bg-body-tertiary p-3 rounded-3">
                                        <div class="col-12 small fw-bold text-secondary mb-1">Новый ребёнок</div>
                                        <div class="col-md-12">
                                            <form method="post" action="<?= e(u('index.php?p=person&id=' . (int)$person['id'])) ?>" class="row g-2">
                                                <?= csrfField() ?><input type="hidden" name="act" value="child_add"><input type="hidden" name="union_id" value="<?= (int)$u['id'] ?>">
                                                <div class="col-md-5"><input class="form-control form-control-sm" name="ch_name" required placeholder="Имя ребёнка"></div>
                                                <div class="col-md-3">
                                                    <select class="form-select form-select-sm" name="ch_gender">
                                                        <option value="u">Пол…</option><option value="m">Мужской</option><option value="f">Женский</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2"><input class="form-control form-control-sm" name="ch_birth" placeholder="дата рождения"></div>
                                                <div class="col-md-2"><input class="form-control form-control-sm" name="ch_birth_place" placeholder="место"></div>
                                                <div class="col-md-3"><input class="form-control form-control-sm" name="ch_death" placeholder="дата смерти (если есть)"></div>
                                                <div class="col-md-2"><button class="btn btn-primary btn-sm w-100">Добавить</button></div>
                                            </form>
                                        </div>
                                        <div class="col-12 small fw-bold text-secondary mt-2 mb-1">Или выбрать существующего человека</div>
                                        <div class="col-md-12">
                                            <form method="post" action="<?= e(u('index.php?p=person&id=' . (int)$person['id'])) ?>" class="row g-2">
                                                <?= csrfField() ?><input type="hidden" name="act" value="child_link"><input type="hidden" name="union_id" value="<?= (int)$u['id'] ?>">
                                                <div class="col-md-10">
                                                    <select class="form-select form-select-sm" name="existing_child" required>
                                                        <option value="">— выберите человека из дерева —</option>
                                                        <?php foreach ($personsList as $pl): if ((int)$pl['id'] === (int)$person['id']) continue; ?>
                                                            <option value="<?= (int)$pl['id'] ?>"><?= e($pl['name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2"><button class="btn btn-outline-secondary btn-sm w-100">Привязать</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($canEdit && $unionsMine): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-soft btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#addSpouseForm"><i class="bi bi-heart me-1"></i>Добавить союз</button>
                        </div>
                    <?php endif; ?>

                    <?php if ($canEdit): ?>
                        <div class="collapse mt-3" id="addSpouseForm">
                            <div class="bg-body-tertiary p-3 rounded-3">
                                <div class="small fw-bold text-secondary mb-2">Новый союз для <?= e($person['name']) ?></div>
                                <form method="post" action="<?= e(u('index.php?p=person&id=' . (int)$person['id'])) ?>" class="row g-2">
                                    <?= csrfField() ?><input type="hidden" name="act" value="spouse_create">
                                    <div class="col-md-4"><input class="form-control form-control-sm" name="sp_name" required placeholder="Имя"></div>
                                    <div class="col-md-2"><select class="form-select form-select-sm" name="sp_gender"><option value="u">Пол…</option><option value="m">Мужской</option><option value="f">Женский</option></select></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm" name="sp_birth" placeholder="родился(ась)"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm" name="sp_death" placeholder="умер(ла)"></div>
                                    <div class="col-md-2"><button class="btn btn-primary btn-sm w-100">Создать союз</button></div>
                                    <div class="col-md-4"><input class="form-control form-control-sm" name="sp_start" placeholder="брак заключён: дата или факт"></div>
                                    <div class="col-md-4"><input class="form-control form-control-sm" name="sp_end" placeholder="расторгнут: дата или факт"></div>
                                    <div class="col-md-4"><input class="form-control form-control-sm" name="sp_note" placeholder="примечание о браке"></div>
                                </form>
                                <div class="small fw-bold text-secondary my-2">Или связать с существующим человеком</div>
                                <form method="post" action="<?= e(u('index.php?p=person&id=' . (int)$person['id'])) ?>" class="row g-2">
                                    <?= csrfField() ?><input type="hidden" name="act" value="spouse_link">
                                    <div class="col-md-8">
                                        <select class="form-select form-select-sm" name="existing_spouse" required>
                                            <option value="">— выберите человека из дерева —</option>
                                            <?php foreach ($personsList as $pl): if ((int)$pl['id'] === (int)$person['id']) continue; ?>
                                                <option value="<?= (int)$pl['id'] ?>"><?= e($pl['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2"><input class="form-control form-control-sm" name="sp_start" placeholder="брак с"></div>
                                    <div class="col-md-2"><button class="btn btn-outline-secondary btn-sm w-100">Создать союз</button></div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <h2 class="h5 fw-bold mb-0"><i class="bi bi-arrow-up-square me-2 text-secondary"></i>Родители</h2>
                    </div>
                    <?php if (!$childUnions): ?>
                        <p class="text-secondary small mb-2">Родители пока не указаны.</p>
                    <?php endif; ?>

                    <?php if ($canEdit): ?>
                        <?php
                        $fatherPerson = null;
                        $motherPerson = null;
                        foreach ($parents as $par) {
                            if (!$fatherPerson && $par['gender'] === 'm') $fatherPerson = $par;
                            if (!$motherPerson && $par['gender'] === 'f') $motherPerson = $par;
                        }
                        ?>
                        <div class="relation-card mb-3">
                            <div class="row g-3">
                                <div class="col-md-6" data-role="father">
                                    <label class="form-label small fw-bold mb-1">Отец</label>
                                    <div class="mb-2 parent-chip" id="chipFather">
                                        <?php if ($fatherPerson): echo pc($fatherPerson); else: ?><span class="badge badge-owner">отец не указан</span><?php endif; ?>
                                    </div>
                                    <input class="form-control form-control-sm parent-input" list="dlFather" data-role="father"
                                           placeholder="Выберите или введите имя" autocomplete="off"
                                           value="<?= e($fatherPerson ? $fatherPerson['name'] : '') ?>">
                                    <datalist id="dlFather">
                                        <?php foreach ($personsList as $pl): if ((int)$pl['id'] === (int)$person['id']) continue; if ($pl['gender'] !== 'm') continue; ?>
                                            <option value="<?= e($pl['name']) ?>" data-id="<?= (int)$pl['id'] ?>"></option>
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                                <div class="col-md-6" data-role="mother">
                                    <label class="form-label small fw-bold mb-1">Мать</label>
                                    <div class="mb-2 parent-chip" id="chipMother">
                                        <?php if ($motherPerson): echo pc($motherPerson); else: ?><span class="badge badge-owner">мать не указана</span><?php endif; ?>
                                    </div>
                                    <input class="form-control form-control-sm parent-input" list="dlMother" data-role="mother"
                                           placeholder="Выберите или введите имя" autocomplete="off"
                                           value="<?= e($motherPerson ? $motherPerson['name'] : '') ?>">
                                    <datalist id="dlMother">
                                        <?php foreach ($personsList as $pl): if ((int)$pl['id'] === (int)$person['id']) continue; if ($pl['gender'] !== 'f') continue; ?>
                                            <option value="<?= e($pl['name']) ?>" data-id="<?= (int)$pl['id'] ?>"></option>
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5 d-flex flex-column gap-4">
                <div class="card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h5 fw-bold mb-0"><i class="bi bi-paperclip me-2 text-secondary"></i>Материалы</h2>
                        <?php if ($canEdit): ?>
                            <a class="btn btn-soft btn-sm" href="<?= e(u('index.php?p=media_add&tree=' . (int)$tree['id'] . '&person=' . (int)$person['id'])) ?>"><i class="bi bi-plus me-1"></i>Добавить</a>
                        <?php endif; ?>
                    </div>
                    <?php if (!$materials && !$archival): ?>
                        <p class="text-secondary small mb-0">К персоне пока ничего не привязано. Добавьте изображение, PDF, видео или аудио и отметьте область/таймкод, относящиеся к <?= e($person['name']) ?>.</p>
                    <?php endif; ?>
                    <?php foreach ($materials as $b): ?>
                        <a class="d-flex align-items-center gap-3 border rounded-3 p-3 mb-2 text-decoration-none media-link" href="<?= e(u('index.php?p=media&id=' . (int)$b['media_id'])) ?>" style="color:var(--text)">
                            <span class="media-ico-sm d-flex align-items-center justify-content-center rounded-3 text-secondary" style="width:44px;height:44px;background:var(--surface-2)"><i class="bi <?= mediaKindIcon($b['kind']) ?>"></i></span>
                            <span class="flex-grow-1 min-w-0">
                                <span class="d-block text-truncate fw-semibold"><?= e($b['title'] ?: ($b['original'] ?: 'Материал')) ?></span>
                                <span class="small text-secondary"><?= e(bindingSummary($b)) ?></span>
                            </span>
                            <i class="bi bi-chevron-right text-secondary"></i>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h5 fw-bold mb-0"><i class="bi bi-archive me-2 text-secondary"></i>Архивные данные</h2>
                        <?php if ($canEdit): ?>
                            <a class="btn btn-soft btn-sm" href="<?= e(u('index.php?p=media_add&tree=' . (int)$tree['id'] . '&person=' . (int)$person['id'] . '&archival=1')) ?>"><i class="bi bi-plus me-1"></i>Добавить запись</a>
                        <?php endif; ?>
                    </div>
                    <p class="text-secondary small">Здесь накапливаются записи из архивов, в которых упоминается <?= e($person['name']) ?>, с привязкой к области отсканированного документа.</p>
                    <?php if (!$archival): ?>
                        <p class="text-secondary small fst-italic mb-0">Записей из архивов пока нет.</p>
                    <?php endif; ?>
                    <?php foreach ($archival as $b): ?>
                        <a class="d-flex align-items-center gap-3 border rounded-3 p-3 mb-2 text-decoration-none media-link" href="<?= e(u('index.php?p=media&id=' . (int)$b['media_id'])) ?>" style="color:var(--text)">
                            <span class="d-flex align-items-center justify-content-center rounded-3" style="width:44px;height:44px;background:var(--accent-soft);color:var(--accent)"><i class="bi <?= mediaKindIcon($b['kind']) ?>"></i></span>
                            <span class="flex-grow-1 min-w-0">
                                <span class="d-block text-truncate fw-semibold"><?= e($b['title'] ?: ($b['original'] ?: 'Архивная запись')) ?></span>
                                <span class="small text-secondary"><?= e(bindingSummary($b)) ?></span>
                            </span>
                            <i class="bi bi-chevron-right text-secondary"></i>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($canEdit): ?>
                        <a class="btn btn-outline-secondary btn-sm w-100 mt-1" href="<?= e(u('index.php?p=archive&tree=' . (int)$tree['id'])) ?>">Все архивные записи дерева</a>
                    <?php endif; ?>
                </div>

                <?php if ($canEdit): ?>
                    <div class="card p-4">
                        <h2 class="h5 fw-bold mb-3 text-danger"><i class="bi bi-trash me-2"></i>Удалить персону</h2>
                        <p class="text-secondary small">Персона будет удалена из дерева вместе со связями и привязками материалов.</p>
                        <form method="post" action="<?= e(u('index.php?p=person&id=' . (int)$person['id'])) ?>" data-confirm="Удалить персону безвозвратно?">
                            <?= csrfField() ?><input type="hidden" name="act" value="delete">
                            <input type="text" class="form-control form-control-sm mb-2" name="confirm_text" placeholder="Введите «УДАЛИТЬ»" required style="max-width:220px">
                            <button class="btn btn-danger btn-sm">Удалить персону</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
    (function () {
        var params = new URLSearchParams(window.location.search);
        var add = params.get('add');
        if (!add) return;
        var map = { spouse: 'addSpouseForm', child: 'addChildForm', parent: 'addParentsForm' };
        var id = map[add];
        if (!id) return;
        var el = document.getElementById(id);
        if (!el) return;
        setTimeout(function () {
            if (window.bootstrap && bootstrap.Collapse) {
                bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).show();
            } else {
                el.classList.add('show');
            }
            setTimeout(function () {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 200);
        }, 200);
    })();
</script>

<script>
    (function () {
        window.CSRF = '<?= e(csrfToken()) ?>';
        window.PERSON_ID = <?= (int)$person['id'] ?>;
        window.TREE_ID = <?= (int)$tree['id'] ?>;
        var state = {};
        function chipHtml(p) {
            var initial = (p.name || '?').charAt(0).toUpperCase();
            var g = p.gender === 'm' ? 'm' : p.gender === 'f' ? 'f' : 'u';
            return '<a class="person-chip p-gender-' + g + '" href="index.php?p=person&id=' + p.id + '">' +
                '<span class="p-ava">' + initial + '</span>' + p.name + '</a>';
        }
        function markSaved(input, ok) {
            input.classList.remove('is-invalid');
            if (ok) {
                var t = input.dataset.role === 'father' ? 'отец' : 'мать';
                input.title = 'Сохранено (' + t + ')';
            }
        }
        document.querySelectorAll('.parent-input').forEach(function (input) {
            var role = input.dataset.role;
            state[role] = input.value;
            input.addEventListener('change', function () {
                var name = input.value.trim();
                if (name === '') return;
                var datalist = document.getElementById(input.getAttribute('list'));
                var pid = null;
                if (datalist) {
                    for (var i = 0; i < datalist.options.length; i++) {
                        if (datalist.options[i].value === name) {
                            pid = datalist.options[i].getAttribute('data-id');
                            break;
                        }
                    }
                }
                var data = {
                    csrf: window.CSRF,
                    tree: window.TREE_ID,
                    person: window.PERSON_ID,
                    role: role,
                    name: name
                };
                if (pid) data.parent_id = pid;
                $.ajax({
                    url: 'index.php?p=api_set_parent',
                    method: 'POST',
                    data: data,
                    dataType: 'json'
                }).done(function (resp) {
                    if (resp && resp.ok && resp.person) {
                        var chip = document.getElementById(role === 'father' ? 'chipFather' : 'chipMother');
                        if (chip) chip.innerHTML = chipHtml(resp.person);
                        markSaved(input, true);
                        if (datalist && !pid) {
                            var o = document.createElement('option');
                            o.value = resp.person.name;
                            o.setAttribute('data-id', resp.person.id);
                            datalist.appendChild(o);
                        }
                    } else {
                        markSaved(input, false);
                        alert((resp && resp.error) ? resp.error : 'Не удалось сохранить.');
                    }
                }).fail(function (xhr) {
                    markSaved(input, false);
                    alert('Ошибка запроса (' + xhr.status + ').');
                });
            });
        });
    })();
</script>

<div class="modal fade" id="avatarViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Фото персоны</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body text-center">
                <?php if ($person['avatar_full'] !== ''): ?>
                    <img src="<?= e(mediaViewUrl($person['avatar_full'])) ?>" alt="" style="max-width:100%;max-height:72vh">
                <?php else: ?>
                    <p class="text-secondary mb-0">Фото не загружено.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="avatarCropModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Фото персоны</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <input type="file" class="form-control mb-3" id="avatarFile" accept="image/*">
                <div class="text-center d-none" id="avatarStageWrap">
                    <div class="avatar-stage" id="avatarStage">
                        <img id="avatarPreview" alt="">
                        <div class="crop-box" id="avatarCropBox"></div>
                    </div>
                    <div class="small text-secondary mt-2">Зажмите мышь и растяните квадрат на фото, затем нажмите «Сохранить».</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="avatarSaveBtn" disabled>Сохранить</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var fullRel = '';
        var crop = null;
        var stage = document.getElementById('avatarStage');
        var wrap = document.getElementById('avatarStageWrap');
        var img = document.getElementById('avatarPreview');
        var box = document.getElementById('avatarCropBox');
        var saveBtn = document.getElementById('avatarSaveBtn');
        var cropModal = document.getElementById('avatarCropModal');

        var openBtn = document.getElementById('avatarOpen');
        if (openBtn) {
            openBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var vm = document.getElementById('avatarViewModal');
                if (vm) {
                    (bootstrap.Modal.getInstance(vm) || new bootstrap.Modal(vm)).show();
                }
            });
        }

        var editBtn = document.getElementById('avatarEditBtn');
        if (editBtn) {
            editBtn.addEventListener('click', function () {
                crop = null;
                fullRel = '';
                box.style.display = 'none';
                wrap.classList.add('d-none');
                saveBtn.disabled = true;
                document.getElementById('avatarFile').value = '';
                var m = bootstrap.Modal.getInstance(cropModal) || new bootstrap.Modal(cropModal);
                m.show();
            });
        }

        document.getElementById('avatarFile').addEventListener('change', function () {
            var f = this.files && this.files[0];
            if (!f) return;
            var fd = new FormData();
            fd.append('photo', f);
            fd.append('csrf', window.CSRF);
            fd.append('person', window.PERSON_ID);
            $.ajax({
                url: 'index.php?p=avatar_upload',
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function (resp) {
                if (resp && resp.ok) {
                    fullRel = resp.full;
                    img.src = resp.full_url;
                    crop = null;
                    box.style.display = 'none';
                    saveBtn.disabled = true;
                } else {
                    alert((resp && resp.error) ? resp.error : 'Не удалось загрузить фото.');
                }
            }).fail(function (xhr) {
                alert('Ошибка загрузки (' + xhr.status + ').');
            });
        });

        img.addEventListener('load', function () {
            wrap.classList.remove('d-none');
        });

        var drawing = false, start = null;
        stage.addEventListener('mousedown', function (e) {
            e.preventDefault();
            drawing = true;
            var r = img.getBoundingClientRect();
            start = { x: e.clientX - r.left, y: e.clientY - r.top };
        });
        window.addEventListener('mousemove', function (e) {
            if (!drawing) return;
            var r = img.getBoundingClientRect();
            var cx = Math.min(Math.max(e.clientX - r.left, 0), r.width);
            var cy = Math.min(Math.max(e.clientY - r.top, 0), r.height);
            var dx = cx - start.x, dy = cy - start.y;
            var side = Math.max(Math.abs(dx), Math.abs(dy));
            var left = dx >= 0 ? start.x : start.x - side;
            var top = dy >= 0 ? start.y : start.y - side;
            side = Math.min(side, r.width - left, r.height - top);
            left = Math.max(0, left);
            top = Math.max(0, top);
            box.style.display = 'block';
            box.style.left = left + 'px';
            box.style.top = top + 'px';
            box.style.width = side + 'px';
            box.style.height = side + 'px';
            var scale = img.naturalWidth / r.width;
            crop = { x: Math.round(left * scale), y: Math.round(top * scale), size: Math.round(side * scale) };
        });
        window.addEventListener('mouseup', function () {
            if (!drawing) return;
            drawing = false;
            if (crop && crop.size > 0) saveBtn.disabled = false;
        });

        saveBtn.addEventListener('click', function () {
            if (!fullRel || !crop) return;
            saveBtn.disabled = true;
            $.ajax({
                url: 'index.php?p=avatar_save',
                method: 'POST',
                data: { csrf: window.CSRF, person: window.PERSON_ID, full: fullRel, x: crop.x, y: crop.y, size: crop.size },
                dataType: 'json'
            }).done(function (resp) {
                if (resp && resp.ok) {
                    location.reload();
                } else {
                    alert((resp && resp.error) ? resp.error : 'Не удалось сохранить фото.');
                    saveBtn.disabled = false;
                }
            }).fail(function (xhr) {
                alert('Ошибка сохранения (' + xhr.status + ').');
                saveBtn.disabled = false;
            });
        });
    })();
</script>
