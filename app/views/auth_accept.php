<div class="container">
    <div class="card auth-card p-4 p-lg-5">
        <div class="auth-logo"><i class="bi bi-envelope-check"></i></div>
        <h1 class="h3 fw-bold text-center mb-2">Активация учётной записи</h1>
        <p class="text-center text-secondary small mb-4">
            Здравствуйте, <strong><?= e($user['name'] ?: $user['email']) ?></strong>!<br>
            Вам назначена роль «<?= e(roleLabel($user['role'])) ?>». Задайте пароль, чтобы активировать учётную запись.
        </p>
        <form method="post" action="<?= e(u('index.php?p=accept&t=' . rawurlencode(getStr('t')))) ?>">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Пароль</label>
                <input type="password" class="form-control" name="password" required minlength="<?= (int)cfg('pwd_min_len') ?>" autocomplete="new-password">
                <div class="form-text">Минимум <?= (int)cfg('pwd_min_len') ?> символов, буквы и цифры.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Повторите пароль</label>
                <input type="password" class="form-control" name="password2" required autocomplete="new-password">
            </div>
            <button class="btn btn-primary w-100 py-2" type="submit">Активировать учётную запись</button>
        </form>
    </div>
</div>
