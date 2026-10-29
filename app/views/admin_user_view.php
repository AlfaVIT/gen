<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <nav class="small mb-3 text-secondary"><a href="<?= e(u('index.php?p=admin_users')) ?>">Пользователи</a> <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> <?= e($target['name'] ?: $target['email']) ?></nav>

            <div class="card p-4 mb-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="avatar-lg p-gender-u"><?= e(mb_substr($target['name'] ?: $target['email'], 0, 1)) ?></span>
                    <div>
                        <h1 class="page-title fs-3 mb-0"><?= e($target['name'] ?: 'Без имени') ?></h1>
                        <div class="text-secondary small"><?= e($target['email']) ?> · создан <?= e(date('d.m.Y H:i', strtotime($target['created_at']))) ?></div>
                    </div>
                </div>
                <?php if ($target['status'] === 'pending'): ?>
                    <div class="alert alert-warning small mb-4">
                        <i class="bi bi-envelope-exclamation me-1"></i>Учётная запись ожидает активации. Приглашение действительно до <?= $target['activation_expires'] ? e(date('d.m.Y H:i', strtotime($target['activation_expires']))) : '—' ?>.
                        <?php if ($inviteLink): ?>
                            <div class="mt-2">Ссылка-приглашение: <code class="user-select-all"><?= e($inviteLink) ?></code></div>
                            <div class="small text-secondary mt-1">Если письмо не доходит (почта не настроена на сервере), отправьте эту ссылку пользователю напрямую.</div>
                        <?php endif; ?>
                        <form method="post" action="<?= e(u('index.php?p=admin_user&id=' . (int)$target['id'])) ?>" class="d-inline mt-2">
                            <?= csrfField() ?><input type="hidden" name="act" value="resend">
                            <button class="btn btn-sm btn-soft mt-2">Отправить приглашение повторно</button>
                        </form>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(u('index.php?p=admin_user&id=' . (int)$target['id'])) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="act" value="update">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Имя</label>
                            <input type="text" class="form-control" name="name" value="<?= e($target['name']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Роль</label>
                            <select class="form-select" name="role">
                                <?php foreach (['admin' => 'Администратор', 'user' => 'Пользователь', 'guest' => 'Гость'] as $rv => $rl): ?>
                                    <option value="<?= $rv ?>" <?= $target['role'] === $rv ? 'selected' : '' ?>><?= $rl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Статус</label>
                            <select class="form-select" name="status">
                                <option value="active" <?= $target['status'] === 'active' ? 'selected' : '' ?>>Активна</option>
                                <option value="pending" <?= $target['status'] === 'pending' ? 'selected' : '' ?>>Ожидает активации</option>
                                <option value="blocked" <?= $target['status'] === 'blocked' ? 'selected' : '' ?>>Заблокирована</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary">Сохранить</button>
                        <?php if ((int)$target['id'] === (int)$me['id']): ?>
                            <span class="small text-secondary ms-2"><i class="bi bi-info-circle me-1"></i>Собственную роль и статус менять нельзя.</span>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="card p-4 mb-4">
                <h2 class="h6 fw-bold mb-3"><i class="bi bi-tree me-2 text-secondary"></i>Деревья пользователя (<?= count($trees) ?>)</h2>
                <?php if (!$trees): ?>
                    <p class="text-secondary small mb-0">Деревьев нет.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($trees as $t): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-semibold"><?= e($t['title']) ?></div>
                                    <div class="small text-secondary"><?= (int)$t['is_public'] === 1 ? 'публичное' : 'личное' ?></div>
                                </div>
                                <a class="btn btn-outline-secondary btn-sm" href="<?= e(u('index.php?p=tree&id=' . (int)$t['id'])) ?>">Открыть</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card p-4 border-danger-subtle">
                <h2 class="h6 fw-bold mb-2 text-danger"><i class="bi bi-trash me-2"></i>Удалить пользователя</h2>
                <p class="text-secondary small">Будут удалены все деревья пользователя вместе с материалами и вложениями. Действие необратимо.</p>
                <form method="post" action="<?= e(u('index.php?p=admin_user&id=' . (int)$target['id'])) ?>" data-confirm="Удалить пользователя и все его данные?">
                    <?= csrfField() ?><input type="hidden" name="act" value="delete">
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        <input type="text" class="form-control" name="confirm_text" placeholder="Введите «УДАЛИТЬ»" required style="max-width:240px" autocomplete="off">
                        <button class="btn btn-danger" <?= (int)$target['id'] === (int)$me['id'] ? 'disabled' : '' ?>>Удалить пользователя</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
