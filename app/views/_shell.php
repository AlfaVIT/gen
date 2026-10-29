<?php
$flash = $flash ?? [];
$user = $user ?? false;
$siteName = $siteName ?? 'Родовое древо';
?>
<header class="app-navbar">
    <nav class="navbar navbar-expand-lg">
        <div class="container-xl">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(u('')) ?>">
                <span class="brand-mark"><img src="<?= e(basePath()) ?>/assets/img/tree-logo.svg" alt="Логотип" width="34" height="34"></span>
                <span class="brand-text"><?= e($siteName) ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-label="Меню">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <?php if (!$user): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(u('')) ?>#about">О сервисе</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= e(u('index.php?p=privacy')) ?>">Конфиденциальность</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= e(u('index.php?p=login')) ?>">Войти</a></li>
                        <li class="nav-item ms-lg-2"><a class="btn btn-primary btn-sm px-3" href="<?= e(u('index.php?p=login')) ?>">Личный кабинет</a></li>
                    <?php else: ?>
                        <?php if ($user['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= e(u('index.php?p=admin_users')) ?>">Пользователи</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(u('index.php?p=trees')) ?>">Деревья</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= e(u('index.php?p=privacy')) ?>">Политика</a></li>
                        <li class="nav-item d-flex align-items-center ms-lg-1">
                            <button class="btn btn-icon theme-toggle" type="button" id="themeToggle" title="Переключить тему"><i class="bi bi-moon-stars"></i></button>
                        </li>
                        <li class="nav-item ms-lg-1">
                            <div class="dropdown">
                                <button class="btn btn-ghost d-flex align-items-center gap-2 py-1 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="avatar-sm"><?= e(mb_substr($user['name'] ?: $user['email'], 0, 1)) ?></span>
                                    <span class="d-none d-md-inline text-truncate" style="max-width:140px"><?= e($user['name'] ?: $user['email']) ?></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li><span class="dropdown-item-text small text-secondary"><?= e($user['email']) ?><br>Роль: <?= e(roleLabel($user['role'])) ?></span></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= e(u('index.php?p=profile')) ?>"><i class="bi bi-person-gear me-2"></i>Профиль и настройки</a></li>
                                    <?php if ($user['role'] === 'user' || $user['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?= e(u('index.php?p=consent')) ?>"><i class="bi bi-shield-lock me-2"></i>Согласие на ПДн</a></li>
                                    <?php endif; ?>
                                    <li><a class="dropdown-item text-danger" href="<?= e(u('index.php?p=logout')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Выйти</a></li>
                                </ul>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<?php if ($flash): ?>
    <div class="container-xl flash-zone">
        <?php foreach ($flash as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
                <?= nl2br(e($f['text'])) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main class="app-main">
    <?= $content ?>
</main>

<div class="cookie-banner" id="cookieBanner" role="dialog" aria-label="Предупреждение об использовании cookie">
    <div class="cookie-banner-inner">
        <div class="cookie-icon"><i class="bi bi-cookie"></i></div>
        <div>
            <div class="fw-bold mb-1">Мы используем cookie</div>
            <p class="mb-2 small">Сайт использует только необходимые cookie-файлы (например, для хранения сессии входа) и не передаёт данные третьим лицам. Подробнее — в <a href="<?= e(u('index.php?p=privacy')) ?>">политике конфиденциальности</a>.</p>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-primary btn-sm" id="cookieAccept">Принять</button>
                <a class="btn btn-outline-secondary btn-sm" href="<?= e(u('index.php?p=privacy')) ?>">Подробнее</a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(basePath()) ?>/assets/js/app.js?20260949"></script>
