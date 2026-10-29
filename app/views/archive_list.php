<div class="container-xl py-4">
    <nav class="small mb-3 text-secondary"><a href="<?= e(u('index.php?p=trees')) ?>">Деревья</a> <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> <a href="<?= e(u('index.php?p=tree&id=' . (int)$tree['id'])) ?>"><?= e($tree['title']) ?></a> <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> Архивные данные</nav>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="tree-ico"><i class="bi bi-archive"></i></div>
            <div>
                <h1 class="page-title mb-0 fs-2">Архивные данные</h1>
                <div class="text-secondary small">Все архивные записи и материалы дерева с привязками к персонам</div>
            </div>
        </div>
        <?php if ($canEdit): ?>
            <a class="btn btn-primary" href="<?= e(u('index.php?p=media_add&tree=' . (int)$tree['id'] . '&archival=1')) ?>"><i class="bi bi-plus-lg me-1"></i>Добавить архивную запись</a>
        <?php endif; ?>
    </div>

    <div class="d-flex gap-2 mb-4 flex-wrap">
        <a class="btn btn-sm <?= $view === 'archive' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(u('index.php?p=archive&tree=' . (int)$tree['id'] . '&view=archive')) ?>"><i class="bi bi-archive me-1"></i>Архивные записи (<?= (int)$archiveCount ?>)</a>
        <a class="btn btn-sm <?= $view === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(u('index.php?p=archive&tree=' . (int)$tree['id'] . '&view=all')) ?>">Все материалы и записи</a>
    </div>

    <?php if (!$rows): ?>
        <div class="card empty-state">
            <div><i class="bi bi-archive"></i></div>
            <h5 class="mt-3">Записей пока нет</h5>
            <p class="small">Добавьте отсканированные документы, выписки из архивов, аудио- и видеозаписи и привяжите их к персонам дерева.</p>
            <?php if ($canEdit): ?>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a class="btn btn-soft" href="<?= e(u('index.php?p=media_add&tree=' . (int)$tree['id'] . '&archival=1')) ?>">Добавить первую запись</a>
                    <a class="btn btn-outline-secondary" href="<?= e(u('index.php?p=media_add&tree=' . (int)$tree['id'] . '&archival=1&kind=note')) ?>">Добавить текстовую запись</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach ($rows as $r):
            $m = $r['media'];
        ?>
            <div class="col-md-6 col-xl-4">
                <div class="card media-tile h-100">
                    <a class="media-thumb text-decoration-none d-block" href="<?= e(u('index.php?p=media&id=' . (int)$m['id'])) ?>">
                        <?php if ($m['file']): $thumbRel = preg_replace('/\.[a-z0-9]+$/i', '_th.jpg', $m['file']); if ($m['kind'] === 'image' && mediaFileReal($thumbRel)): ?>
                            <img src="<?= e(mediaViewUrl($thumbRel)) ?>" alt="" loading="lazy">
                        <?php elseif ($m['kind'] === 'image'): ?>
                            <img src="<?= e(mediaViewUrl($m['file'])) ?>" alt="" loading="lazy">
                        <?php elseif ($m['kind'] === 'pdf'): ?>
                            <i class="bi bi-file-earmark-pdf"></i>
                        <?php elseif ($m['kind'] === 'video'): ?>
                            <i class="bi bi-film"></i>
                        <?php elseif ($m['kind'] === 'audio'): ?>
                            <i class="bi bi-music-note-beamed"></i>
                        <?php else: ?>
                            <i class="bi bi-file-earmark-text"></i>
                        <?php endif; ?>
                        <?php endif; ?>
                        <span class="kind-badge"><?= e(mediaKindLabel($m['kind'])) ?></span>
                        <?php if ((int)$m['archival'] === 1): ?><span class="archive-ribbon">архив</span><?php endif; ?>
                    </a>
                    <div class="p-3 d-flex flex-column gap-2 flex-grow-1">
                        <div class="flex-grow-1">
                            <a class="fw-bold text-decoration-none d-block" href="<?= e(u('index.php?p=media&id=' . (int)$m['id'])) ?>" style="color:var(--text)">
                                <?= e($m['title'] ?: ($m['original'] ?: 'Без названия')) ?>
                            </a>
                            <?php if ($m['note']): ?><p class="small text-secondary text-truncate-2 mb-1 mt-1"><?= e($m['note']) ?></p><?php endif; ?>
                        </div>
                        <?php if ($r['bindings']): ?>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <span class="small text-secondary me-1">Упомянуты:</span>
                                <?php foreach ($r['bindings'] as $b): ?>
                                    <?php if ($b['ghost']): ?>
                                        <span class="badge badge-owner small">Персона</span>
                                    <?php else: ?>
                                        <a class="badge badge-soft text-decoration-none" href="<?= e(u('index.php?p=person&id=' . (int)$b['person_id'])) ?>"><?= e($b['person_name']) ?></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex gap-2 mt-2 pt-2 border-top" style="border-color:var(--border)!important">
                            <a class="btn btn-outline-secondary btn-sm flex-grow-1" href="<?= e(u('index.php?p=media&id=' . (int)$m['id'])) ?>">Открыть</a>
                            <?php if ($canEdit): ?>
                                <a class="btn btn-outline-secondary btn-sm" href="<?= e(u('index.php?p=media_edit&id=' . (int)$m['id'])) ?>" title="Редактировать и привязки"><i class="bi bi-gear"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($canEdit): ?>
        <div class="card p-4 mt-4">
            <h2 class="h5 fw-bold mb-2"><i class="bi bi-info-circle me-2 text-secondary"></i>Как работать с архивными записями</h2>
            <p class="text-secondary small mb-0">Загрузите отсканированный документ как изображение или PDF и создайте привязку к персоне — укажите область документа, где встречается эта персона. Для видео- и аудиозаписей укажите таймкод дорожки. При просмотре документа область будет подсвечиваться, а записи автоматически появятся в разделе «Архивные данные» соответствующей персоны.</p>
        </div>
    <?php endif; ?>
</div>
