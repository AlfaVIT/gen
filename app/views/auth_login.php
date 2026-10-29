<div class="container">
    <div class="card auth-card p-4 p-lg-5">
        <div class="auth-logo"><img src="<?= e(basePath()) ?>/assets/img/tree-logo.svg" alt="Логотип" width="46" height="46"></div>
        <h1 class="h3 fw-bold text-center mb-1">Вход в архив</h1>
        <p class="text-center text-secondary small mb-4"><?= e(cfg('site_tagline')) ?></p>
        <form method="post" action="<?= e(u('index.php?p=login')) ?>">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">E-mail</label>
                <input type="email" class="form-control" name="email" required autofocus>
            </div>
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="form-label">Пароль</label>
                    <a href="<?= e(u('index.php?p=forgot')) ?>" class="small">Забыли пароль?</a>
                </div>
                <input type="password" class="form-control" name="password" required>
            </div>
            <button class="btn btn-primary w-100 py-2" type="submit">Войти</button>
        </form>
        <div class="text-center mt-4 small text-secondary">
            Нет доступа? Регистрацию можно запросить у администратора по e-mail:
            <a href="mailto:<?= e(cfg('app_email')) ?>"><?= e(cfg('app_email')) ?></a>
        </div>
    </div>
</div>
