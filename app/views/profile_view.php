<div class="container-xl py-4">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="avatar-lg p-gender-u"><?= e(mb_substr($user['name'] ?: $user['email'], 0, 1)) ?></span>
                    <div>
                        <div class="h5 fw-bold mb-0"><?= e($user['name'] ?: '—') ?></div>
                        <div class="text-secondary small"><?= e($user['email']) ?></div>
                    </div>
                </div>
                <div class="kv"><span class="k">Роль</span><span class="v"><?= e(roleLabel($user['role'])) ?></span></div>
                <div class="kv"><span class="k">Статус</span><span class="v text-success">Активна</span></div>
                <div class="kv"><span class="k">Дата создания</span><span class="v"><?= e(date('d.m.Y', strtotime($user['created_at']))) ?></span></div>
                <div class="kv"><span class="k">Последний вход</span><span class="v"><?= $user['last_login'] ? e(date('d.m.Y H:i', strtotime($user['last_login']))) : '—' ?></span></div>
                <div class="kv"><span class="k">Моих деревьев</span><span class="v"><?= (int)$treeCount ?></span></div>
                <div class="kv"><span class="k">Согласие на ПДн</span>
                    <span class="v"><?php if ((int)$user['consent'] === 1): ?><span class="text-success"><i class="bi bi-check-circle"></i> дано</span><?php else: ?><span class="text-danger">не дано</span><?php endif; ?></span>
                </div>
            </div>
        </div>
        <div class="col-lg-8 d-flex flex-column gap-4">
            <div class="card p-4">
                <h2 class="h5 fw-bold mb-3"><i class="bi bi-person me-2 text-secondary"></i>Имя и отображение</h2>
                <form method="post" action="<?= e(u('index.php?p=profile')) ?>" class="row g-2 align-items-end">
                    <?= csrfField() ?>
                    <input type="hidden" name="act" value="update_name">
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="name" value="<?= e($user['name']) ?>" placeholder="Как вас отображать">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100">Сохранить</button>
                    </div>
                </form>
            </div>

            <div class="card p-4">
                <h2 class="h5 fw-bold mb-3"><i class="bi bi-key me-2 text-secondary"></i>Смена пароля</h2>
                <form method="post" action="<?= e(u('index.php?p=profile')) ?>" class="row g-3">
                    <?= csrfField() ?>
                    <input type="hidden" name="act" value="change_password">
                    <div class="col-md-4">
                        <label class="form-label">Текущий пароль</label>
                        <input type="password" class="form-control" name="old_password" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Новый пароль</label>
                        <input type="password" class="form-control" name="password" required minlength="<?= (int)cfg('pwd_min_len') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Повторите новый пароль</label>
                        <input type="password" class="form-control" name="password2" required>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Изменить пароль</button>
                    </div>
                </form>
            </div>

            <?php if ($canPublish): ?>
            <div class="card p-4">
                <h2 class="h5 fw-bold mb-3"><i class="bi bi-shield-lock me-2 text-secondary"></i>Обработка персональных данных</h2>
                <?php if ((int)$user['consent'] === 1): ?>
                    <p class="text-secondary small mb-3">Согласие на обработку персональных данных дано <?= $user['consent_at'] ? e(date('d.m.Y H:i', strtotime($user['consent_at']))) : '' ?>. Вы можете размещать сведения в своих деревьях.</p>
                    <p class="small mb-3"><i class="bi bi-exclamation-triangle text-warning me-1"></i>Отзыв согласия приведёт к безвозвратному удалению всех внесённых вами данных.</p>
                    <a class="btn btn-soft" href="<?= e(u('index.php?p=consent')) ?>">Управлять согласием</a>
                <?php else: ?>
                    <p class="text-secondary small mb-3">Для размещения сведений необходимо дать согласие на обработку персональных данных в соответствии с ФЗ-152.</p>
                    <a class="btn btn-primary" href="<?= e(u('index.php?p=consent')) ?>">Дать согласие на обработку ПДн</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="card p-4">
                <h2 class="h5 fw-bold mb-2"><i class="bi bi-eye me-2 text-secondary"></i>Роль «Гость»</h2>
                <p class="text-secondary small mb-0">Ваша роль позволяет просматривать только открытые (публичные) данные. Для создания деревьев и внесения сведений запросите у администратора роль «Пользователь».</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
