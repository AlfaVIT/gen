<div class="container-xl py-4">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
            <div class="section-label"><?= $isGuest ? 'Доступные данные' : 'Мои деревья' ?></div>
            <h1 class="page-title mt-1 mb-0">Генеалогические деревья</h1>
        </div>
        <?php if (!$isGuest): ?>
            <?php if ($consentGiven): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTreeModal"><i class="bi bi-plus-lg me-1"></i>Новое дерево</button>
            <?php else: ?>
                <a class="btn btn-soft" href="<?= e(u('index.php?p=consent')) ?>"><i class="bi bi-shield-lock me-1"></i>Дать согласие и создавать деревья</a>
            <?php endif; ?>
        <?php else: ?>
            <span class="badge badge-soft"><i class="bi bi-eye me-1"></i>Режим просмотра</span>
        <?php endif; ?>
    </div>

    <?php if (!$trees): ?>
        <div class="card empty-state">
            <div><i class="bi bi-tree"></i></div>
            <h5 class="mt-3">Пока нет деревьев</h5>
            <?php if ($isGuest): ?>
                <p class="small">Владельцы ещё не открыли публичных деревьев. Когда они появятся, вы сможете их просматривать.</p>
            <?php elseif (!$consentGiven): ?>
                <p class="small">Дайте согласие на обработку персональных данных, чтобы создать первое дерево.</p>
            <?php else: ?>
                <p class="small">Нажмите «Новое дерево», чтобы начать родословную.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach ($trees as $t):
            $mine = (int)$t['owner_id'] === (int)$user['id'];
            $canEdit = !$isGuest && ($mine || $user['role'] === 'admin');
        ?>
            <div class="col-md-6 col-xl-4">
                <div class="tree-card card h-100 p-4" onclick="location.href='<?= e(u('index.php?p=tree&id=' . (int)$t['id'])) ?>'">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="tree-ico"><img src="<?= e(basePath()) ?>/assets/img/tree-logo.svg" alt="" width="100%" height="100%"></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h3 class="h6 fw-bold mb-1 text-truncate"><?= e($t['title']) ?></h3>
                                <?php if ((int)$t['is_public'] === 1): ?>
                                    <span class="badge badge-soft"><i class="bi bi-globe2 me-1"></i>публичное</span>
                                <?php else: ?>
                                    <span class="badge-owner badge"><i class="bi bi-lock me-1"></i>личное</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!$mine): ?>
                                <div class="tree-meta small"><i class="bi bi-person me-1"></i><?= e($t['owner_name'] ?: $t['owner_email']) ?></div>
                            <?php endif; ?>
                            <?php if ($t['description']): ?>
                                <p class="text-secondary small mb-1 text-truncate-2"><?= e($t['description']) ?></p>
                            <?php endif; ?>
                            <div class="tree-meta mt-2 d-flex align-items-center gap-3">
                                <span><i class="bi bi-people me-1"></i><?= (int)$t['persons_count'] ?> персон</span>
                                <span class="small">обновлено <?= e(date('d.m.Y', strtotime($t['updated_at']))) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3 pt-3 border-top" style="border-color:var(--border)!important" onclick="event.stopPropagation()">
                        <a class="btn btn-soft btn-sm flex-grow-1" href="<?= e(u('index.php?p=tree&id=' . (int)$t['id'])) ?>">Открыть</a>
                        <?php if ($canEdit && (int)$user['consent'] === 1): ?>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= e(u('index.php?p=tree_settings&id=' . (int)$t['id'])) ?>" title="Настройки"><i class="bi bi-gear"></i></a>
                        <?php endif; ?>
                        <?php if ($mine || $user['role'] === 'admin'): ?>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= e(u('index.php?p=export&id=' . (int)$t['id'])) ?>" title="Скачать XML"><i class="bi bi-download"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!$isGuest && $consentGiven): ?>
<div class="modal fade" id="createTreeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= e(u('index.php?p=trees')) ?>">
            <?= csrfField() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-tree me-2 text-secondary"></i>Новое дерево</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Название <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="title" required maxlength="200" placeholder="Например: Род Кузнецовых">
                </div>
                <div class="mb-3">
                    <label class="form-label">Описание</label>
                    <textarea class="form-control" name="description" rows="2" maxlength="2000"></textarea>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="publicSwitch" name="is_public" value="1">
                    <label class="form-check-label" for="publicSwitch">Публичное дерево (доступно другим участникам для просмотра)</label>
                </div>
                <p class="small text-secondary mt-2 mb-0"><i class="bi bi-shield-check me-1"></i>Скрытые персоны и их потомки не будут видны никому, кроме вас и администратора.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-primary">Создать дерево</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
