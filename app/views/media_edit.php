<?php
$isGeom = in_array($media['kind'], ['image', 'pdf'], true);
$isTime = in_array($media['kind'], ['video', 'audio'], true);
$previewUrl = $media['url'] !== '' ? $media['url'] : $fileUrl;
$init = json_encode([
    'kind' => $media['kind'],
    'preview' => $previewUrl,
    'bindings' => $bindings,
    'active' => $activeBinding,
], JSON_UNESCAPED_UNICODE);
?>
<div class="container-xl py-4">
    <nav class="small mb-3 text-secondary">
        <a href="<?= e(u('index.php?p=trees')) ?>">Деревья</a> <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i>
        <a href="<?= e(u('index.php?p=tree&id=' . (int)$tree['id'])) ?>"><?= e($tree['title']) ?></a>
        <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i>
        <a href="<?= e(u('index.php?p=media&id=' . (int)$media['id'])) ?>"><?= e($media['title'] ?: ($media['original'] ?: 'Материал')) ?></a>
        <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> Редактирование
    </nav>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="tree-ico"><i class="bi <?= e(mediaKindIcon($media['kind'])) ?>"></i></div>
            <div>
                <h1 class="page-title mb-0 fs-3"><?= e(mediaKindLabel($media['kind'])) ?></h1>
                <div class="text-secondary small"><?= e($media['title'] ?: ($media['original'] ?: 'Без названия')) ?> · <?= e($tree['title']) ?></div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(u('index.php?p=media&id=' . (int)$media['id'])) ?>">Открыть как читатель</a>
            <a class="btn btn-outline-secondary" href="<?= e(u('index.php?p=archive&tree=' . (int)$tree['id'])) ?>">Архивные данные</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card p-3">
                <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                    <div class="fw-bold"><i class="bi bi-eye me-2 text-secondary"></i>Просмотр и выделение областей</div>
                    <?php if ($activeBinding && ($isGeom || $isTime)): ?>
                        <span class="badge badge-soft">Режим выделения: привязка к «<?= e($activeBinding['person_name']) ?>»</span>
                    <?php endif; ?>
                </div>

                <?php if ($media['kind'] === 'image'): ?>
                    <div class="text-center">
                        <div class="region-stage" id="regionStage">
                            <img src="<?= e($previewUrl) ?>" id="stageImg" alt="Изображение" style="max-height:640px">
                            <div class="region-select-overlay" id="drawBox"></div>
                        </div>
                    </div>
                    <div class="small text-secondary mt-2"><i class="bi bi-hand-index me-1"></i><?= $activeBinding ? 'Зажмите мышь и растяните рамку вокруг персоны на изображении, затем нажмите «Сохранить область».' : 'Выберите привязку слева, чтобы начать выделение.' ?></div>
                <?php elseif ($media['kind'] === 'pdf'): ?>
                    <div class="pdf-wrap p-3">
                        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                            <button class="btn btn-sm btn-outline-secondary" id="pdfPrev"><i class="bi bi-chevron-left"></i></button>
                            <span class="small">Страница <b id="pdfPageNo">1</b></span>
                            <button class="btn btn-sm btn-outline-secondary" id="pdfNext"><i class="bi bi-chevron-right"></i></button>
                            <input type="number" class="form-control form-control-sm" id="pdfPageInput" min="1" value="1" style="width:90px">
                            <span class="small text-secondary" id="pdfLoadState">загрузка…</span>
                        </div>
                        <div class="pdf-page" id="pdfStage" style="display:none;position:relative">
                            <canvas id="pdfCanvas"></canvas>
                            <div class="region-select-overlay" id="drawBox"></div>
                        </div>
                    </div>
                <?php elseif ($media['kind'] === 'video' || $media['kind'] === 'audio'): ?>
                    <?php if ($media['kind'] === 'video'): ?>
                        <video id="mediaPlayer" controls preload="metadata" style="width:100%;max-height:520px" src="<?= e($previewUrl) ?>"></video>
                    <?php else: ?>
                        <audio id="mediaPlayer" controls preload="metadata" style="width:100%" src="<?= e($previewUrl) ?>"></audio>
                    <?php endif; ?>
                    <div class="small text-secondary mt-2"><i class="bi bi-clock me-1"></i>Для привязки используйте кнопки «начало» / «конец» в списке справа, либо укажите время вручную (секунды).</div>
                <?php else: ?>
                    <div class="bg-body-tertiary rounded-3 p-4">
                        <div class="section-label mb-2">Текст записи</div>
                        <div class="text-wrap"><?= nl2br(e($media['note'] ?: '—')) ?></div>
                        <?php if ($media['url']): ?><a href="<?= e($media['url']) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>Открыть источник</a><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-xl-4 d-flex flex-column gap-4">
            <div class="card p-4">
                <h2 class="h6 fw-bold mb-3"><i class="bi bi-people me-2 text-secondary"></i>Привязки к персонам</h2>
                <?php if (!$bindings): ?>
                    <p class="text-secondary small">Привязок нет. Выберите персону ниже, чтобы связать материал с человеком.</p>
                <?php endif; ?>

                <div class="d-flex flex-column gap-2 mb-3">
                    <?php foreach ($bindings as $b): $act = $activeBinding && $activeBinding['id'] === $b['id']; ?>
                        <div class="border rounded-3 p-2 px-3 <?= $act ? 'border-primary' : '' ?>" style="border-color:var(--border)!important">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="fw-semibold small flex-grow-1"><?= $b['ghost'] ? 'Персона' : e($b['person_name']) ?></span>
                                <span class="badge badge-soft small"><?= e(bindingTypeBadge($b)) ?></span>
                            </div>
                            <div class="small text-secondary mt-1"><?= e(bindingGeomText($b)) ?></div>
                            <div class="d-flex gap-1 mt-1 flex-wrap">
                                <?php if (!$b['ghost']): ?>
                                    <a class="iconbtn" title="Открыть персону" href="<?= e(u('index.php?p=person&id=' . (int)$b['person_id'])) ?>"><i class="bi bi-box-arrow-up-right"></i></a>
                                <?php endif; ?>
                                <?php if ($isGeom || $isTime): ?>
                                    <a class="iconbtn" title="Выделить область/время" href="<?= e(u('index.php?p=media_edit&id=' . (int)$media['id'] . '&b=' . (int)$b['id'])) ?>"><i class="bi bi-crop"></i></a>
                                <?php endif; ?>
                                <form method="post" action="<?= e(u('index.php?p=media_edit&id=' . (int)$media['id'])) ?>" class="d-inline" data-confirm="Удалить привязку?">
                                    <?= csrfField() ?><input type="hidden" name="act" value="binding_delete"><input type="hidden" name="binding_id" value="<?= (int)$b['id'] ?>">
                                    <button class="iconbtn danger" title="Удалить привязку"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                            <?php if ($act && ($isGeom || $isTime)): ?>
                                <div class="mt-2 border-top pt-2" style="border-color:var(--border)!important">
                                    <?php if ($isTime): ?>
                                        <div class="d-flex gap-2 align-items-center mb-2">
                                            <input type="number" class="form-control form-control-sm" id="tstart" value="<?= (float)$b['tstart'] ?>" min="0" step="0.1" style="width:110px">
                                            <span class="text-secondary">—</span>
                                            <input type="number" class="form-control form-control-sm" id="tend" value="<?= (float)$b['tend'] ?>" min="0" step="0.1" style="width:110px">
                                        </div>
                                        <div class="d-flex gap-2 mb-2 flex-wrap">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="setStart">Начало из плеера</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="setEnd">Конец из плеера</button>
                                        </div>
                                        <div class="small text-secondary mb-2" id="timePreview"></div>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e(u('index.php?p=media_edit&id=' . (int)$media['id'])) ?>" id="bindSaveForm" class="d-flex gap-2 flex-wrap">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="act" value="binding_update">
                                        <input type="hidden" name="binding_id" value="<?= (int)$b['id'] ?>">
                                        <input type="hidden" name="btype" value="<?= e($b['btype']) ?>">
                                        <input type="hidden" name="page" value="<?= (int)$b['page'] ?>">
                                        <input type="hidden" name="x" value="<?= (float)$b['x'] ?>">
                                        <input type="hidden" name="y" value="<?= (float)$b['y'] ?>">
                                        <input type="hidden" name="w" value="<?= (float)$b['w'] ?>">
                                        <input type="hidden" name="h" value="<?= (float)$b['h'] ?>">
                                        <input type="hidden" name="tstart" value="<?= (float)$b['tstart'] ?>">
                                        <input type="hidden" name="tend" value="<?= (float)$b['tend'] ?>">
                                        <button class="btn btn-primary btn-sm" id="saveBindingBtn">Сохранить выделение</button>
                                        <a class="btn btn-outline-secondary btn-sm" href="<?= e(u('index.php?p=media_edit&id=' . (int)$media['id'])) ?>">Отмена</a>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!$media['note'] || true): ?>
                <form method="post" action="<?= e(u('index.php?p=media_edit&id=' . (int)$media['id'])) ?>" class="d-flex gap-2">
                    <?= csrfField() ?>
                    <input type="hidden" name="act" value="binding_add">
                    <select class="form-select form-select-sm" name="person_id" required>
                        <option value="">— добавить привязку к персоне —</option>
                        <?php foreach ($persons as $pl): ?>
                            <option value="<?= (int)$pl['id'] ?>"><?= e($pl['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-soft btn-sm flex-shrink-0">Привязать</button>
                </form>
                <?php endif; ?>
            </div>

            <div class="card p-4">
                <h2 class="h6 fw-bold mb-3"><i class="bi bi-sliders me-2 text-secondary"></i>Параметры материала</h2>
                <form method="post" action="<?= e(u('index.php?p=media_edit&id=' . (int)$media['id'])) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="act" value="meta">
                    <div class="mb-2">
                        <label class="form-label small">Название</label>
                        <input type="text" class="form-control form-control-sm" name="title" value="<?= e($media['title']) ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Текст / описание</label>
                        <textarea class="form-control form-control-sm" name="note" rows="4"><?= e($media['note']) ?></textarea>
                    </div>
                    <?php if ($media['file']): ?>
                        <div class="small text-secondary mb-2">Файл: <?= e($media['original']) ?> (<?= (int)round($media['size'] / 1024) ?> КБ)</div>
                    <?php endif; ?>
                    <div class="mb-2">
                        <label class="form-label small">Внешняя ссылка</label>
                        <input type="url" class="form-control form-control-sm" name="url" value="<?= e($media['url']) ?>">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="archivalSwitch" name="archival" value="1" <?= (int)$media['archival'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="archivalSwitch">Архивная запись</label>
                    </div>
                    <button class="btn btn-primary btn-sm">Сохранить параметры</button>
                </form>
            </div>

            <div class="card p-4">
                <h2 class="h6 fw-bold mb-2 text-danger"><i class="bi bi-trash me-2"></i>Удалить материал</h2>
                <form method="post" action="<?= e(u('index.php?p=media_edit&id=' . (int)$media['id'])) ?>" data-confirm="Удалить материал безвозвратно?">
                    <?= csrfField() ?><input type="hidden" name="act" value="media_delete">
                    <input type="text" class="form-control form-control-sm mb-2" name="confirm_text" placeholder="Введите «УДАЛИТЬ»" required>
                    <button class="btn btn-danger btn-sm">Удалить материал</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    window.MEDIA_EDIT = <?= $init ?>;
</script>
