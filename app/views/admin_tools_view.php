<div class="container-xl py-4">
    <div class="d-flex align-items-end justify-content-between gap-3 mb-4 flex-wrap">
        <div>
            <div class="section-label">Администрирование</div>
            <h1 class="page-title mt-1 mb-0">Починка семейных союзов</h1>
        </div>
        <a class="btn btn-outline-secondary" href="<?= e(u('index.php?p=admin_users')) ?>">К пользователям</a>
    </div>

    <?php if (!$rows): ?>
        <div class="card empty-state">
            <div><i class="bi bi-check2-circle"></i></div>
            <h5 class="mt-3">Все союзы корректны</h5>
            <p class="small">Союзов с тремя и более участниками не найдено. Каждый семейный союз должен состоять не более чем из двух супругов.</p>
        </div>
    <?php endif; ?>

    <div class="alert alert-warning small mb-4">
        <i class="bi bi-info-circle me-1"></i>Союз с более чем двумя участниками появляется, когда второго супруга добавляют «внутрь» уже существующего союза. Исправление: выберите пару, которая остаётся родителями детей в этом союзе, а остальные участники будут вынесены в отдельные брачные союзы с указанным «общим» супругом.
    </div>

    <div class="row g-4">
        <?php foreach ($rows as $r):
            $u = $r['union'];
            $members = $r['members'];
            $children = $r['children'];
            $tree = loadTree($u['tree_id']);
        ?>
            <div class="col-lg-6">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2 flex-wrap">
                        <div>
                            <div class="fw-bold">Союз #<?= (int)$u['id'] ?> — <?= e($tree ? $tree['title'] : '—') ?></div>
                            <div class="small text-secondary">Брак: <?= e($u['start_date'] !== '' ? $u['start_date'] : '—') ?> — <?= e($u['end_date'] !== '' ? $u['end_date'] : 'активен') ?></div>
                        </div>
                        <span class="badge badge-owner">участников: <?= count($members) ?></span>
                    </div>

                    <div class="mb-2">
                        <span class="section-label small">Участники союза</span>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            <?php foreach ($members as $m): ?>
                                <a class="person-chip p-gender-<?= e($m['gender']) ?>" href="<?= e(u('index.php?p=person&id=' . (int)$m['id'])) ?>">
                                    <span class="p-ava"><?= e(mb_substr($m['name'], 0, 1)) ?></span><?= e($m['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <span class="section-label small">Дети в этом союзе</span>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            <?php if ($children): ?>
                                <?php foreach ($children as $c): ?>
                                    <a class="person-chip p-gender-<?= e($c['gender']) ?>" href="<?= e(u('index.php?p=person&id=' . (int)$c['id'])) ?>">
                                        <span class="p-ava"><?= e(mb_substr($c['name'], 0, 1)) ?></span><?= e($c['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="small text-secondary">нет</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="post" action="<?= e(u('index.php?p=tools')) ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="act" value="split">
                        <input type="hidden" name="union_id" value="<?= (int)$u['id'] ?>">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Остаются в союзе (родители детей)</label>
                                <select class="form-select form-select-sm" name="keepA" required>
                                    <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Остаются в союзе (родители детей)</label>
                                <select class="form-select form-select-sm" name="keepB" required>
                                    <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Новый брак создавать с…</label>
                                <select class="form-select form-select-sm" name="pairWith">
                                    <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary btn-sm">Вынести остальных в отдельные союзы</button>
                                <div class="form-text small">Все участники, кроме выбранной пары, будут вынесены в отдельные брачные союзы с выбранным «общим» супругом. Дети останутся в текущем союзе.</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
