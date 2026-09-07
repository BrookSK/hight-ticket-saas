<?php
/**
 * Admin dashboard — foundation placeholder.
 *
 * @var App\Libraries\Translator $t
 * @var string|null $userName
 */
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e(__('common.nav.dashboard')) ?></h1>
        <?php if (!empty($userName)): ?>
            <p class="text-muted mb-0"><?= e(__('auth.welcome', ['name' => $userName])) ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="card-surface">
    <div class="empty-state">
        <i class="bi bi-clipboard-data empty-state__icon" aria-hidden="true"></i>
        <h2 class="h5"><?= e(__('common.empty.title')) ?></h2>
        <p class="mb-3"><?= e(__('common.empty.message')) ?></p>
    </div>
</div>
