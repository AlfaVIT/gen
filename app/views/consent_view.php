<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card p-4 p-lg-5">
                <?php if ((int)$user['consent'] !== 1): ?>
                    <div class="section-label">ФЗ-152</div>
                    <h1 class="page-title mt-1 mb-3">Согласие на обработку персональных данных</h1>
                    <p class="text-secondary">Размещая сведения о людях в генеалогических деревьях, вы обрабатываете персональные данные. В соответствии с Федеральным законом № 152-ФЗ это возможно только с согласия субъекта данных либо на ином законном основании, за которое отвечаете вы.</p>
                    <div class="alert alert-warning small">
                        <strong>Важно:</strong> вы самостоятельно несёте ответственность за сведения, которые вносите. Вносите данные только тех лиц, в отношении которых у вас есть основания (согласие субъекта, собственные данные, публичные исторические сведения и т. п.).
                    </div>
                    <h5 class="fw-bold">Что происходит с данными</h5>
                    <ul class="text-secondary small">
                        <li>внесённые сведения хранятся только на этом сайте и <strong>не передаются третьим лицам</strong>;</li>
                        <li>доступ к ним имеют вы и — если дерево публичное — другие участники сервиса с ролью не ниже «Гость»;</li>
                        <li>скрытые персоны и их потомки не раскрываются никомему, кроме владельца дерева и администратора;</li>
                        <li>вы можете в любой момент изменить или удалить внесённые данные, а также отозвать настоящее согласие.</li>
                    </ul>
                    <form method="post" action="<?= e(u('index.php?p=consent')) ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="act" value="agree">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="consentCheck" required>
                            <label class="form-check-label small text-secondary" for="consentCheck">
                                Я даю согласие на обработку персональных данных, размещаемых мной в генеалогических деревьях, на условиях настоящей политики, и подтверждаю, что имею основания для внесения этих сведений.
                            </label>
                        </div>
                        <button class="btn btn-primary">Дать согласие и продолжить</button>
                    </form>
                <?php else: ?>
                    <div class="section-label">ФЗ-152</div>
                    <h1 class="page-title mt-1 mb-3">Управление согласием</h1>
                    <div class="alert alert-success small">
                        <i class="bi bi-check-circle me-1"></i>Согласие на обработку персональных данных дано <?= $user['consent_at'] ? e(date('d.m.Y H:i', strtotime($user['consent_at']))) : '' ?>. Вы можете размещать сведения.
                    </div>
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle me-1"></i><strong>Отзыв согласия</strong> приведёт к безвозвратному удалению всех внесённых вами данных — всех ваших деревьев вместе с персонами, связями, материалами и архивными записями. Восстановить их будет невозможно (сохраните резервную копию в XML, если дерево вам нужно).
                    </div>
                    <?php if ($treeList): ?>
                        <h5 class="fw-bold">Будет удалено</h5>
                        <ul class="list-group list-group-flush mb-4 small">
                            <?php foreach ($treeList as $t): ?>
                                <li class="list-group-item bg-transparent d-flex justify-content-between px-0">
                                    <span><i class="bi bi-tree me-2 text-secondary"></i><?= e($t['title']) ?></span>
                                    <span class="text-secondary">персон: <?= (int)$t['persons_count'] ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-secondary small">У вас пока нет деревьев — отзыв согласия не удалит никаких данных.</p>
                    <?php endif; ?>
                    <form method="post" action="<?= e(u('index.php?p=consent')) ?>" data-confirm="Вы уверены, что хотите отозвать согласие? Все внесённые вами данные будут удалены безвозвратно.">
                        <?= csrfField() ?>
                        <input type="hidden" name="act" value="revoke">
                        <div class="mb-3">
                            <label class="form-label">Введите слово <strong>УДАЛИТЬ</strong> для подтверждения</label>
                            <input type="text" class="form-control" name="confirm_text" autocomplete="off" required style="max-width:280px">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="understand" name="understand" value="1" required>
                            <label class="form-check-label small text-secondary" for="understand">Я понимаю, что все мои данные будут удалены безвозвратно.</label>
                        </div>
                        <button class="btn btn-danger" type="submit">Отозвать согласие и удалить данные</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
