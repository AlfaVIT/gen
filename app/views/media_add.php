<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <nav class="small mb-3 text-secondary">
                <a href="<?= e(u('index.php?p=trees')) ?>">Деревья</a> <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i>
                <a href="<?= e(u('index.php?p=tree&id=' . (int)$tree['id'])) ?>"><?= e($tree['title']) ?></a>
                <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> <?= $archival ? 'Архивная запись' : 'Материал' ?>
            </nav>
            <div class="card p-4 p-lg-5">
                <div class="d-flex align-items-center gap-3 mb-1">
                    <div class="tree-ico"><i class="bi bi-paperclip"></i></div>
                    <div>
                        <h1 class="page-title fs-3 mb-0"><?= $archival ? 'Новая архивная запись' : 'Новый материал' ?></h1>
                        <div class="text-secondary small"><?= e($tree['title']) ?><?= $prePerson ? ' · будет привязана к персоне' : '' ?></div>
                    </div>
                </div>
                <?php if ($archival): ?>
                    <div class="alert alert-warning small mt-3 mb-3"><i class="bi bi-archive me-1"></i>Запись будет помечена как «архивная» и появится в разделе «Архивные данные» дерева и персон.</div>
                <?php endif; ?>

                <form method="post" action="<?= e(u('index.php?p=media_add&tree=' . (int)$tree['id'] . ($prePerson ? '&person=' . (int)$prePerson : '') . ($archival ? '&archival=1' : ''))) ?>" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="archival" value="<?= $archival ? '1' : '0' ?>">

                    <label class="form-label fw-bold">Тип</label>
                    <div class="row g-2 mb-4" id="kindCards">
                        <?php $kinds = [
                            ['image', 'bi-image', 'Изображение', 'Фото, сканы документов — область выделяется прямо на картинке'],
                            ['pdf', 'bi-file-earmark-pdf', 'PDF', 'Многостраничные документы с областью на странице'],
                            ['video', 'bi-film', 'Видео', 'Ролик с таймкодом, относящимся к персоне'],
                            ['audio', 'bi-music-note-beamed', 'Аудио', 'Запись с таймкодом дорожки'],
                            ['note', 'bi-file-earmark-text', 'Текстовая запись', 'Выписка, текст записи из архива'],
                        ]; ?>
                        <?php foreach ($kinds as $k): ?>
                            <div class="col-md-4 col-6">
                                <label class="kind-card border rounded-3 p-3 d-flex gap-2 align-items-start h-100" style="cursor:pointer">
                                    <input class="form-check-input mt-1 kind-radio" type="radio" name="kind" value="<?= $k[0] ?>" <?= ($kindPreset === $k[0] || (!$kindPreset && $k[0] === 'image')) ? 'checked' : '' ?>>
                                    <span>
                                        <i class="bi <?= $k[1] ?> me-1" style="color:var(--accent)"></i> <strong><?= e($k[2]) ?></strong>
                                        <span class="d-block small text-secondary fw-normal mt-1"><?= e($k[3]) ?></span>
                                    </span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Название</label>
                        <input type="text" class="form-control" name="title" maxlength="250" placeholder="Например: Метрическая запись о рождении, 1890 г.">
                    </div>

                    <div class="mb-3 field-file" data-kinds="image pdf video audio">
                        <label class="form-label">Файл <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="file" id="fileInput" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.mp4,.webm,.mov,.m4v,.ogv,.mp3,.wav,.ogg,.m4a,.aac,.flac">
                        <div class="form-text">Допустимые типы: изображения (JPG/PNG/GIF/WebP), PDF, видео (MP4/WebM/MOV), аудио (MP3/WAV/OGG/M4A/AAC/FLAC). До <?= (int)round($maxUpload / 1048576) ?> МБ.</div>
                    </div>

                    <div class="mb-3 field-url d-none" data-kinds="video audio pdf image">
                        <label class="form-label">…или внешняя ссылка на материал</label>
                        <input type="url" class="form-control" name="url" placeholder="https://…">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Текст / описание записи</label>
                        <textarea class="form-control" name="note" rows="4"><?= $archival ? '' : '' ?></textarea>
                        <div class="form-text">Для текстовой записи архива заполните здесь содержание выписки.</div>
                    </div>

                    <div class="form-check form-switch mb-4 d-none" id="archivalSwitchWrap">
                        <input class="form-check-input" type="checkbox" role="switch" id="archivalSwitch" <?= $archival ? 'checked disabled' : '' ?>>
                        <label class="form-check-label" for="archivalSwitch">Пометить как архивную запись</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary px-4"><?= $prePerson ? 'Добавить и привязать' : 'Добавить материал' ?></button>
                        <a class="btn btn-outline-secondary" href="<?= e(u('index.php?p=tree&id=' . (int)$tree['id'])) ?>">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    document.querySelectorAll('.kind-card').forEach(function (card) {
        card.addEventListener('click', function () { this.querySelector('input').checked = true; updateFields(); });
    });
    function updateFields() {
        var kind = document.querySelector('input[name=kind]:checked');
        var v = kind ? kind.value : '';
        document.querySelectorAll('[data-kinds]').forEach(function (el) {
            var list = (el.getAttribute('data-kinds') || '').split(' ');
            el.classList.toggle('d-none', list.indexOf(v) === -1);
        });
        var file = document.getElementById('fileInput');
        if (v === 'note') file.required = false; else file.required = true;
    }
    updateFields();
</script>
