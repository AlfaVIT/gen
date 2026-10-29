<?php
require __DIR__ . '/app/bootstrap.php';
$GLOBALS['__base'] = basePath();

if (isPost()) {
    if (!adminHasAdmin()) {
        checkCsrf();
        $email = mb_strtolower(postStr('email'));
        $name = postStr('name');
        $pwd = (string)($_POST['password'] ?? '');
        $pwd2 = (string)($_POST['password2'] ?? '');
        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Некорректный e-mail.';
        }
        if ($name === '') {
            $errors[] = 'Укажите имя.';
        }
        $perr = validatePassword($pwd);
        if ($perr !== null) {
            $errors[] = $perr;
        }
        if ($pwd !== $pwd2) {
            $errors[] = 'Пароли не совпадают.';
        }
        if (!$errors) {
            $st = db()->prepare("SELECT id FROM users WHERE role='admin'");
            $st->execute();
            $hasAdmin = (bool)$st->fetch();
            if (!$hasAdmin) {
                $st = db()->prepare('INSERT INTO users (email, name, password_hash, role, status, consent, consent_at, created_at) VALUES (?,?,?,?,?,1,?,?)');
                $st->execute([$email, $name, password_hash($pwd, PASSWORD_DEFAULT), 'admin', 'active', nowSql(), nowSql()]);
                $id = (int)db()->lastInsertId();
                loginUserById($id);
                header('Location: ' . u('index.php?p=admin_users'));
                exit;
            }
            $errors[] = 'Администратор уже существует.';
        }
        if ($errors) {
            $flash = implode('<br>', array_map('htmlspecialchars', $errors));
        }
    }
}
if (adminHasAdmin()) {
    $done = true;
}
$siteName = cfg('site_name');
$accent = cfg('accent');
?>
<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(basePath()) ?>/assets/img/tree-logo.svg">
    <title>Установка — <?= htmlspecialchars($siteName) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { min-height:100vh; display:flex; align-items:center; justify-content:center; margin:0;
               background:linear-gradient(135deg,#f7f1e6,#ede3cf); font-family:system-ui,sans-serif; padding:20px; }
        .box { max-width:460px; width:100%; background:#fff; border-radius:20px; padding:36px; box-shadow:0 18px 50px rgba(80,60,25,.18); }
        .logo { width:60px;height:60px;border-radius:50%;background:#f4e9cf;border:1px solid #d8c9a3;
                display:flex;align-items:center;justify-content:center;margin-bottom:18px;overflow:hidden;padding:8px }
        .form-control { border-radius:12px; padding:.65rem .85rem }
        .btn-primary { background:<?= $accent ?>; border:none; border-radius:12px; padding:.7rem }
        .btn-primary:hover { filter:brightness(1.08) }
    </style>
</head>
<body>
<div class="box">
    <div class="logo"><img src="<?= htmlspecialchars(basePath()) ?>/assets/img/tree-logo.svg" alt="Логотип" width="44" height="44"></div>
    <h1 style="font-size:1.35rem;font-weight:800;margin:0 0 4px">Начальная настройка</h1>
    <p style="color:#777;margin:0 0 20px;font-size:.92rem">Создайте первую учётную запись администратора. Регистрация на сайте открыта только через администратора.</p>
    <?php if (isset($done)): ?>
        <div class="alert alert-success">Учётная запись администратора уже создана. <a href="<?= htmlspecialchars(u('index.php?p=login')) ?>">Перейти ко входу</a>.</div>
    <?php else: ?>
        <?php if (!empty($flash)): ?><div class="alert alert-danger"><?= $flash ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
            <div class="mb-3"><label class="form-label">E-mail администратора</label>
                <input type="email" class="form-control" name="email" required autofocus></div>
            <div class="mb-3"><label class="form-label">Имя</label>
                <input type="text" class="form-control" name="name" required></div>
            <div class="mb-3"><label class="form-label">Пароль</label>
                <input type="password" class="form-control" name="password" required minlength="8">
                <div class="form-text">Минимум 8 символов, буквы и цифры.</div></div>
            <div class="mb-4"><label class="form-label">Повторите пароль</label>
                <input type="password" class="form-control" name="password2" required></div>
            <button class="btn btn-primary w-100">Создать администратора</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
