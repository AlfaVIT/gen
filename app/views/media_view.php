<?php
$previewUrl = $media['url'] !== '' ? $media['url'] : ($media['file'] ? mediaViewUrl($media['file']) : '');
$init = json_encode([
    'kind' => $media['kind'],
    'preview' => $previewUrl,
    'bindings' => $bindings,
], JSON_UNESCAPED_UNICODE);
?>
<div class="container-xl py-4">
    <nav class="small mb-3 text-secondary">
        <a href="<?= e(u('index.php?p=trees')) ?>">Деревья</a> <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i>
        <a href="<?= e(u('index.php?p=tree&id=' . (int)$tree['id'])) ?>"><?= e($tree['title']) ?></a>
        <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> <?= e($media['title'] ?: ($media['original'] ?: 'Материал')) ?>
    </nav>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="tree-ico"><i class="bi <?= e(mediaKindIcon($media['kind'])) ?>"></i></div>
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="page-title mb-0 fs-2"><?= e($media['title'] ?: ($media['original'] ?: 'Без названия')) ?></h1>
                    <?php if ((int)$media['archival'] === 1): ?><span class="badge badge-soft"><i class="bi bi-archive me-1"></i>архивная запись</span><?php endif; ?>
                </div>
                <div class="text-secondary small"><?= e(mediaKindLabel($media['kind'])) ?> · <?= e($tree['title']) ?><?= $media['file'] ? ' · ' . e($media['original']) : '' ?></div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(u('index.php?p=archive&tree=' . (int)$tree['id'])) ?>">Архивные данные</a>
            <?php if ($canEdit): ?>
                <a class="btn btn-primary" href="<?= e(u('index.php?p=media_edit&id=' . (int)$media['id'])) ?>"><i class="bi bi-pencil me-1"></i>Редактировать</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($media['note']): ?>
        <div class="card p-4 mb-4">
            <div class="section-label mb-2">Описание / текст записи</div>
            <div class="text-wrap"><?= nl2br(e($media['note'])) ?></div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-xl-9">
            <div class="card p-3">
                <?php if ($media['kind'] === 'image'): ?>
                    <div class="text-center"><div class="region-stage" id="regionStage"><img src="<?= e($previewUrl) ?>" id="stageImg" alt="Изображение"></div></div>
                <?php elseif ($media['kind'] === 'pdf'): ?>
                    <div class="pdf-wrap p-3">
                        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                            <button class="btn btn-sm btn-outline-secondary" id="pdfPrev"><i class="bi bi-chevron-left"></i></button>
                            <span class="small">Страница <b id="pdfPageNo">1</b></span>
                            <button class="btn btn-sm btn-outline-secondary" id="pdfNext"><i class="bi bi-chevron-right"></i></button>
                            <input type="number" class="form-control form-control-sm" id="pdfPageInput" min="1" value="1" style="width:90px">
                            <span class="small text-secondary" id="pdfLoadState">загрузка…</span>
                        </div>
                        <div class="pdf-page" id="pdfStage" style="position:relative;display:none"><canvas id="pdfCanvas"></canvas></div>
                    </div>
                <?php elseif ($media['kind'] === 'video'): ?>
                    <video id="mediaPlayer" controls preload="metadata" style="width:100%;max-height:620px" src="<?= e($previewUrl) ?>"></video>
                <?php elseif ($media['kind'] === 'audio'): ?>
                    <div class="p-4"><audio id="mediaPlayer" controls preload="metadata" style="width:100%" src="<?= e($previewUrl) ?>"></audio></div>
                <?php else: ?>
                    <div class="bg-body-tertiary rounded-3 p-5 text-center text-secondary"><i class="bi bi-file-earmark-text" style="font-size:3rem"></i><div class="mt-2">Текстовая запись (без встроенного просмотра)</div></div>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2 mt-3 flex-wrap">
                <?php if ($media['file']): ?><a class="btn btn-outline-secondary btn-sm" href="<?= e($previewUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>Открыть оригинал</a><?php endif; ?>
                <?php if ($media['url']): ?><a class="btn btn-outline-secondary btn-sm" href="<?= e($media['url']) ?>" target="_blank" rel="noopener"><i class="bi bi-link-45deg me-1"></i>Внешний источник</a><?php endif; ?>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card p-4">
                <h2 class="h6 fw-bold mb-3"><i class="bi bi-people me-2 text-secondary"></i>К кому относится</h2>
                <?php if (!$bindings): ?>
                    <p class="text-secondary small">С материалом не связано ни одной персоны.</p>
                <?php endif; ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($bindings as $i => $b): ?>
                        <div class="border rounded-3 p-2 px-3 binding-chip" data-idx="<?= $i ?>" <?= $b['ghost'] ? '' : 'data-href="' . e(u('index.php?p=person&id=' . (int)$b['person_id'])) . '"' ?>>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge region-dot" style="background:hsl(<?= (int)($i * 47 % 360) ?>,55%,50%)">&nbsp;</span>
                                <?php if ($b['ghost']): ?>
                                    <span class="fw-semibold small">Персона <i class="bi bi-eye-slash text-secondary"></i></span>
                                <?php else: ?>
                                    <span class="fw-semibold small"><?= e($b['person_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="small text-secondary mt-1 ms-4"><?= e(bindingSummary(array_merge($b, ['kind' => $media['kind']]))) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    window.MEDIA_VIEW = <?= $init ?>;
</script>
