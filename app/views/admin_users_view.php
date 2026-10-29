<div class="container-xl py-4">
    <div class="d-flex align-items-end justify-content-between gap-3 mb-4 flex-wrap">
        <div>
            <div class="section-label">Администрирование</div>
            <h1 class="page-title mt-1 mb-0">Пользователи и гости</h1>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newUserModal"><i class="bi bi-person-plus me-1"></i>Создать учётную запись</button>
    </div>

    <div class="alert alert-warning small mb-4">
        <i class="bi bi-envelope me-1"></i>Регистрация открыта только через администратора. Новая учётная запись получает письмо-приглашение и активируется по ссылке из него. Способ отправки писем: <b><?= e(strtoupper(cfg('email_mode'))) ?></b>.
    </div>

    <div class="card p-3 table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr><th>Пользователь</th><th>Роль</th><th>Статус</th><th class="text-center">Деревья</th><th>Создан</th><th>Вход</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-sm p-gender-u"><?= e(mb_substr($u['name'] ?: $u['email'], 0, 1)) ?></span>
                            <div>
                                <div class="fw-semibold"><?= e($u['name'] ?: '—') ?></div>
                                <div class="small text-secondary"><?= e($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-soft' : 'badge-owner' ?>"><?= e(roleLabel($u['role'])) ?></span></td>
                    <td><span class="badge text-bg-<?= e(statusClass($u['status'])) ?>"><?= e(statusLabel($u['status'])) ?></span></td>
                    <td class="text-center"><?= (int)$u['tree_count'] ?></td>
                    <td class="small text-secondary"><?= e(date('d.m.Y', strtotime($u['created_at']))) ?></td>
                    <td class="small text-secondary"><?= $u['last_login'] ? e(date('d.m.Y', strtotime($u['last_login']))) : '—' ?></td>
                    <td class="text-end">
                        <a class="btn btn-outline-secondary btn-sm" href="<?= e(u('index.php?p=admin_user&id=' . (int)$u['id'])) ?>">Открыть</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="newUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= e(u('index.php?p=admin_users')) ?>">
            <?= csrfField() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2 text-secondary"></i>Новая учётная запись</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">E-mail <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Имя (как отображать)</label>
                    <input type="text" class="form-control" name="name">
                </div>
                <div class="mb-3">
                    <label class="form-label">Роль</label>
                    <select class="form-select" name="role">
                        <option value="user">Пользователь — ведёт свои деревья</option>
                        <option value="guest">Гость — только просмотр открытых данных</option>
                    </select>
                </div>
                <p class="small text-secondary mb-0"><i class="bi bi-envelope-check me-1"></i>На указанный e-mail будет отправлено письмо со ссылкой для активации. Если почта не настроена, ссылку можно скопировать в карточке пользователя.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-primary">Создать и отправить приглашение</button>
            </div>
        </form>
    </div>
</div>
