<div class="container">
    <div class="card auth-card p-4 p-lg-5">
        <div class="auth-logo"><i class="bi bi-key"></i></div>
        <h1 class="h3 fw-bold text-center mb-2">Восстановление пароля</h1>
        <p class="text-center text-secondary small mb-4">Укажите e-mail, привязанный к учётной записи. Мы отправим ссылку для сброса пароля.</p>
        <form method="post" action="<?= e(u('index.php?p=forgot')) ?>">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">E-mail</label>
                <input type="email" class="form-control" name="email" required autofocus>
            </div>
            <button class="btn btn-primary w-100 py-2" type="submit">Отправить ссылку</button>
        </form>
        <div class="text-center mt-4">
            <a href="<?= e(u('index.php?p=login')) ?>" class="small"><i class="bi bi-arrow-left me-1"></i>Вернуться ко входу</a>
        </div>
    </div>
</div>
