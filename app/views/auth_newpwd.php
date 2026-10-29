<div class="container">
    <div class="card auth-card p-4 p-lg-5">
        <div class="auth-logo"><i class="bi bi-shield-lock"></i></div>
        <h1 class="h3 fw-bold text-center mb-1"><?= e($heading) ?></h1>
        <p class="text-center text-secondary small mb-4">Придумайте новый пароль для входа в систему.</p>
        <form method="post" action="<?= e(u('index.php?p=' . $action . '&t=' . rawurlencode($token))) ?>">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Новый пароль</label>
                <input type="password" class="form-control" name="password" required minlength="<?= (int)cfg('pwd_min_len') ?>" autocomplete="new-password">
                <div class="form-text">Минимум <?= (int)cfg('pwd_min_len') ?> символов, буквы и цифры.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Повторите пароль</label>
                <input type="password" class="form-control" name="password2" required autocomplete="new-password">
            </div>
            <button class="btn btn-primary w-100 py-2" type="submit">Сохранить пароль</button>
        </form>
    </div>
</div>
