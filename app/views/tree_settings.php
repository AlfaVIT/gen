<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <nav class="small mb-3 text-secondary"><a href="<?= e(u('index.php?p=trees')) ?>">Деревья</a> <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> <a href="<?= e(u('index.php?p=tree&id=' . (int)$tree['id'])) ?>"><?= e($tree['title']) ?></a> <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> Настройки</nav>
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="tree-ico" style="width:46px;height:46px;padding:8px"><img src="<?= e(basePath()) ?>/assets/img/tree-logo.svg" alt="" width="100%" height="100%"></div>
                <div>
                    <h1 class="page-title fs-2 mb-0">Настройки дерева</h1>
                    <div class="text-secondary small">персон: <?= (int)$stats['persons'] ?> · союзов: <?= (int)$stats['unions'] ?> · материалов: <?= (int)$stats['media'] ?></div>
                </div>
                <a class="btn btn-outline-secondary ms-auto" href="<?= e(u('index.php?p=tree&id=' . (int)$tree['id'])) ?>"><i class="bi bi-arrow-left me-1"></i>К дереву</a>
            </div>

            <div class="card p-4 mb-4">
                <h2 class="h5 fw-bold mb-3"><i class="bi bi-sliders me-2 text-secondary"></i>Основные параметры</h2>
                <form method="post" action="<?= e(u('index.php?p=tree_settings&id=' . (int)$tree['id'])) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="act" value="update">
                    <div class="mb-3">
                        <label class="form-label">Название <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required value="<?= e($tree['title']) ?>" maxlength="200">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание</label>
                        <textarea class="form-control" name="description" rows="3"><?= e($tree['description']) ?></textarea>
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="publicSwitch" name="is_public" value="1" <?= (int)$tree['is_public'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="publicSwitch">Публичное дерево</label>
                        <div class="form-text">Открытые данные дерева будут доступны другим участникам сервиса для просмотра. Скрытые персоны остаются невидимыми.</div>
                    </div>
                    <button class="btn btn-primary" <?= $canEdit ? '' : 'disabled' ?>><?= $canEdit ? 'Сохранить настройки' : 'Требуется согласие на обработку ПДн' ?></button>
                    <?php if (!$canEdit): ?><a class="btn btn-soft ms-2" href="<?= e(u('index.php?p=consent')) ?>">Дать согласие</a><?php endif; ?>
                </form>
            </div>

            <div class="card p-4 mb-4">
                <h2 class="h5 fw-bold mb-1"><i class="bi bi-download me-2 text-secondary"></i>Резервная копия (XML)</h2>
                <p class="text-secondary small">Скачайте дерево в формате XML — все персоны, связи, материалы и архивные записи сохранятся в файле (вложения до <?= (int)round(cfg('embed_base64_max') / 1048576) ?> МБ встраиваются в XML).</p>
                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-outline-secondary" href="<?= e(u('index.php?p=export&id=' . (int)$tree['id'])) ?>"><i class="bi bi-file-earmark-arrow-down me-1"></i>Скачать XML</a>
                </div>
            </div>

            <div class="card p-4 mb-4">
                <h2 class="h5 fw-bold mb-1"><i class="bi bi-upload me-2 text-secondary"></i>Восстановить дерево из XML</h2>
                <p class="text-secondary small">Импорт создаст новое дерево на основе загруженного файла. Восстановление доступно при данном согласии на обработку персональных данных.</p>
                <form method="post" action="<?= e(u('index.php?p=tree_settings&id=' . (int)$tree['id'])) ?>" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="act" value="import">
                    <div class="d-flex gap-2 flex-wrap align-items-end">
                        <div class="flex-grow-1" style="min-width:220px">
                            <input type="file" class="form-control" name="xmlfile" accept=".xml,text/xml,application/xml" required>
                        </div>
                        <button class="btn btn-soft">Восстановить</button>
                    </div>
                </form>
            </div>

            <div class="card p-4 border-danger-subtle">
                <h2 class="h5 fw-bold mb-1 text-danger"><i class="bi bi-trash me-2"></i>Удаление дерева</h2>
                <p class="text-secondary small">Действие необратимо: будут удалены все персоны, связи, материалы и вложения. Перед удалением рекомендуется скачать XML-копию.</p>
                <form method="post" action="<?= e(u('index.php?p=tree_settings&id=' . (int)$tree['id'])) ?>" data-confirm="Удалить дерево безвозвратно?">
                    <?= csrfField() ?>
                    <input type="hidden" name="act" value="delete">
                    <div class="d-flex gap-2 flex-wrap align-items-end">
                        <div class="flex-grow-1" style="min-width:220px">
                            <label class="form-label small text-secondary mb-1">Введите «УДАЛИТЬ» для подтверждения</label>
                            <input type="text" class="form-control" name="confirm_text" required autocomplete="off">
                        </div>
                        <button class="btn btn-danger" <?= $canEdit ? '' : 'disabled' ?>>Удалить дерево</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
