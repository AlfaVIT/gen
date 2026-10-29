<section class="hero container-xl">
    <div class="row align-items-center g-5 py-lg-4">
        <div class="col-lg-7">
            <span class="hero-eyebrow"><img src="<?= e(basePath()) ?>/assets/img/tree-logo.svg" alt="" width="20" height="20" style="display:inline-block"> Личный генеалогический архив</span>
            <h1 class="mt-3">Сохраните историю <span class="serif-accent">своей семьи</span></h1>
            <p class="hero-sub mt-3">Удобный инструмент для ведения родословной: деревья, личности, семейные союзы, материалы с привязкой к изображениям и архивные записи. Все данные остаются под вашим контролем.</p>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <a class="btn btn-primary btn-lg px-4" href="<?= e(u('index.php?p=login')) ?>">Войти в архив</a>
                <a class="btn btn-outline-secondary btn-lg px-4" href="<?= e(u('index.php?p=privacy')) ?>">Политика данных</a>
            </div>
            <p class="small text-secondary mt-4 mb-0">Сайт не предусматривает передачу данных третьим лицам. Ответственность за вносимые сведения несёт пользователь.</p>
        </div>
        <div class="col-lg-5">
            <div class="card landing-card p-4">
                <div class="row g-3">
                    <div class="col-6"><div class="stat-chip h-100"><div class="num">+∞</div><div class="small text-secondary">поколений в дереве</div></div></div>
                    <div class="col-6"><div class="stat-chip h-100"><div class="num">XML</div><div class="small text-secondary">экспорт и восстановление</div></div></div>
                    <div class="col-6"><div class="stat-chip h-100"><div class="num">152</div><div class="small text-secondary">защита персональных данных</div></div></div>
                    <div class="col-6"><div class="stat-chip h-100"><div class="num">2</div><div class="small text-secondary">темы оформления</div></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container-xl py-5" id="about">
    <div class="d-flex align-items-end justify-content-between mb-4">
        <div>
            <div class="section-label">Возможности</div>
            <h2 class="page-title mt-1">Что умеет сервис</h2>
        </div>
    </div>
    <div class="row g-4">
        <?php $features = [
            ['bi-person-plus', 'Персоны и семьи', 'Имя, пол, даты и места жизни, описание, соцсети. Супруги с датами заключения и расторжения брака, дети, предки и потомки.'],
            ['bi-shield-lock', 'Приватность', 'Деревья бывают личными и публичными. Скрытая персона скрывает и себя, и своих потомков — остальные видят лишь структуру с пометкой «Персона».'],
            ['bi-image', 'Материалы', 'Изображения, PDF, видео и аудио. К любой персоне привязывается область на изображении/странице PDF или таймкод дорожки с подсветкой при просмотре.'],
            ['bi-archive', 'Архивные данные', 'Раздел для записей из архивов с привязкой к области отсканированного документа. Записи накапливаются и по каждой персоне тоже.'],
            ['bi-box-arrow-down', 'Экспорт XML', 'Любое дерево скачивается в XML со всеми данными и вложениями, а затем восстанавливается.'],
            ['bi-people', 'Разграничение прав', 'Администратор управляет всем, Пользователь ведёт деревья, Гость просматривает открытые данные.'],
        ]; ?>
        <?php foreach ($features as $f): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 p-4">
                    <div class="benefit-ico"><i class="bi <?= $f[0] ?>"></i></div>
                    <h5 class="fw-bold"><?= e($f[1]) ?></h5>
                    <p class="text-secondary mb-0 small"><?= e($f[2]) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container-xl py-4 pb-5">
    <div class="card p-4 p-lg-5 border-0 shadow-sm">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <div class="section-label">Как начать</div>
                <h3 class="fw-bold mt-1">Доступ к сервису</h3>
                <p class="text-secondary mt-2">Регистрация на сайте открыта только по приглашению администратора. Чтобы получить учётную запись, напишите администратору по электронной почте.</p>
                <ul class="list-unstyled d-flex flex-column gap-2 mt-3 text-secondary">
                    <li><i class="bi bi-envelope-check me-2 text-success"></i>Запросите регистрацию у администратора по e-mail</li>
                    <li><i class="bi bi-key me-2 text-success"></i>Примите приглашение по ссылке из письма и задайте пароль</li>
                    <li><i class="bi bi-tree me-2 text-success"></i>Дайте согласие на обработку персональных данных — и ведите родословную</li>
                </ul>
            </div>
            <div class="col-lg-5">
                <div class="p-4 rounded-4" style="background:var(--accent-soft)">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="brand-mark"><i class="bi bi-envelope"></i></div>
                        <div>
                            <div class="fw-bold">Контакты администратора</div>
                            <a href="mailto:<?= e(cfg('app_email')) ?>"><?= e(cfg('app_email')) ?></a>
                        </div>
                    </div>
                    <p class="small text-secondary mb-0 mt-3">В письме укажите желаемую роль (Пользователь или Гость) и адрес для приглашения. Администратор создаст учётную запись.</p>
                </div>
            </div>
        </div>
    </div>
</section>
