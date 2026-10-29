<div class="container">
    <div class="card auth-card p-4 p-lg-5 text-center">
        <div class="auth-logo"><i class="bi bi-x-circle"></i></div>
        <h1 class="h4 fw-bold mb-2"><?= e($title) ?></h1>
        <p class="text-secondary mb-4"><?= e($text) ?></p>
        <a class="btn btn-primary" href="<?= e(u('index.php?p=login')) ?>">К странице входа</a>
    </div>
</div>
