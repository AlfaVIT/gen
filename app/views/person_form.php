<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav class="small mb-3 text-secondary">
                <a href="<?= e(u('index.php?p=trees')) ?>">Деревья</a> <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i>
                <a href="<?= e(u('index.php?p=tree&id=' . (int)$tree['id'])) ?>"><?= e($tree['title']) ?></a>
                <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> <?= $mode === 'edit' ? 'Редактирование персоны' : 'Новая персона' ?>
            </nav>
            <div class="card p-4 p-lg-5">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="tree-ico"><i class="bi bi-person-plus"></i></div>
                    <div>
                        <h1 class="page-title fs-3 mb-0"><?= $mode === 'edit' ? 'Редактирование персоны' : 'Новая персона' ?></h1>
                        <div class="text-secondary small">Дерево: <?= e($tree['title']) ?></div>
                    </div>
                    <?php if ($mode === 'edit'): ?>
                        <a class="btn btn-outline-secondary ms-auto" href="<?= e(u('index.php?p=person&id=' . (int)$person['id'])) ?>">Назад к карточке</a>
                    <?php else: ?>
                        <a class="btn btn-outline-secondary ms-auto" href="<?= e(u('index.php?p=tree&id=' . (int)$tree['id'])) ?>">К дереву</a>
                    <?php endif; ?>
                </div>

                <form method="post" action="<?= $mode === 'edit'
                    ? e(u('index.php?p=person_edit&id=' . (int)$person['id']))
                    : e(u('index.php?p=person_new&tree=' . (int)$tree['id'] . (getStr('parent_of') ? '&parent_of=' . (int)getStr('parent_of') : ''))) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="act" value="<?= $mode === 'edit' ? 'update' : 'create' ?>">

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Имя и фамилия <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required value="<?= $mode === 'edit' ? e($person['name']) : '' ?>" maxlength="300">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Пол</label>
                            <select class="form-select" name="gender">
                                <option value="u" <?= $mode === 'edit' && $person['gender'] === 'u' ? 'selected' : '' ?>>Не указан</option>
                                <option value="m" <?= $mode === 'edit' && $person['gender'] === 'm' ? 'selected' : '' ?>>Мужской</option>
                                <option value="f" <?= $mode === 'edit' && $person['gender'] === 'f' ? 'selected' : '' ?>>Женский</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Дата рождения</label>
                            <input type="text" class="form-control" name="birth_date" value="<?= $mode === 'edit' ? e($person['birth_date']) : '' ?>" placeholder="например: 12.03.1875 или «около 1800»">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Место рождения</label>
                            <input type="text" class="form-control" name="birth_place" value="<?= $mode === 'edit' ? e($person['birth_place']) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Дата смерти</label>
                            <input type="text" class="form-control" name="death_date" value="<?= $mode === 'edit' ? e($person['death_date']) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Место смерти</label>
                            <input type="text" class="form-control" name="death_place" value="<?= $mode === 'edit' ? e($person['death_place']) : '' ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Биографическое описание</label>
                            <textarea class="form-control" name="description" rows="5"><?= $mode === 'edit' ? e($person['description']) : '' ?></textarea>
                            <div class="form-text">Расскажите о человеке: занятия, события, воспоминания. Вы отвечаете за достоверность вносимых сведений.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <label class="form-label fw-bold">Соцсети и внешние ссылки</label>
                    <div id="socialRows">
                        <?php if ($socials): ?>
                            <?php foreach ($socials as $s): ?>
                                <div class="social-row row g-2 mb-2 align-items-center">
                                    <div class="col-md-4"><input type="text" class="form-control" name="soc_label[]" placeholder="Название (например: ВКонтакте)" value="<?= e($s['label']) ?>"></div>
                                    <div class="col-md-7"><input type="url" class="form-control" name="soc_url[]" placeholder="https://…" value="<?= e($s['url']) ?>"></div>
                                    <div class="col-md-1 text-end"><button type="button" class="iconbtn danger row-remove" title="Убрать"><i class="bi bi-x-lg"></i></button></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="social-row row g-2 mb-2 align-items-center">
                                <div class="col-md-4"><input type="text" class="form-control" name="soc_label[]" placeholder="Название (например: ВКонтакте)"></div>
                                <div class="col-md-7"><input type="url" class="form-control" name="soc_url[]" placeholder="https://…"></div>
                                <div class="col-md-1 text-end"><button type="button" class="iconbtn danger row-remove" title="Убрать"><i class="bi bi-x-lg"></i></button></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="addSocialRow"><i class="bi bi-plus me-1"></i>Добавить ссылку</button>

                    <hr class="my-4">

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="personPublic" name="is_public" value="1" <?= $mode !== 'edit' || (int)$person['is_public'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="personPublic">Публичная персона</label>
                        <div class="form-text">Если дерево публичное, открытая персона видна другим участникам. Снятие отметки скрывает эту персону и всех её потомков (для остальных они отображаются как «Персона»).</div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary px-4"><?= $mode === 'edit' ? 'Сохранить изменения' : 'Добавить персону' ?></button>
                        <a class="btn btn-outline-secondary" href="<?= $mode === 'edit'
                            ? e(u('index.php?p=person&id=' . (int)$person['id']))
                            : e(u('index.php?p=tree&id=' . (int)$tree['id'])) ?>">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('addSocialRow').addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'social-row row g-2 mb-2 align-items-center';
        row.innerHTML = '<div class="col-md-4"><input type="text" class="form-control" name="soc_label[]" placeholder="Название"></div>' +
            '<div class="col-md-7"><input type="url" class="form-control" name="soc_url[]" placeholder="https://…"></div>' +
            '<div class="col-md-1 text-end"><button type="button" class="iconbtn danger row-remove"><i class="bi bi-x-lg"></i></button></div>';
        document.getElementById('socialRows').appendChild(row);
    });
</script>
