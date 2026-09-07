<?php
/**
 * Administrative (app) layout.
 *
 * Wraps authenticated back-office pages with a responsive sidebar + navbar.
 * Mobile First: the sidebar collapses into an offcanvas on small screens.
 * Shows an impersonation banner when active. Content arrives in $content.
 *
 * @var string $content
 * @var App\Libraries\Translator $t
 */
$view = app('view');
$activePath = $activePath ?? '/app';
$isImpersonating = auth()?->isImpersonating() ?? false;
?><!doctype html>
<html lang="<?= e($locale ?? 'pt-BR') ?>">
<head>
<?= $view->partial('layouts.partials.head', get_defined_vars()) ?>
</head>
<body>
<div class="d-flex">
    <nav class="app-sidebar d-none d-lg-block p-3" aria-label="<?= e(__('admin.nav.dashboard')) ?>">
        <a href="/app" class="d-flex align-items-center gap-2 fw-bold mb-4" style="color: var(--color-primary-700);">
            <i class="bi bi-boxes fs-4" aria-hidden="true"></i>
            <span><?= e(config_value('system_name', 'LRV Web')) ?></span>
        </a>
        <?= $view->partial('layouts.partials.admin-sidebar', get_defined_vars()) ?>
    </nav>

    <div class="flex-grow-1 min-vw-0">
        <header class="app-navbar d-flex align-items-center px-3 px-lg-4">
            <button class="btn btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#mobileSidebar" aria-controls="mobileSidebar"
                    aria-label="<?= e(__('admin.nav.dashboard')) ?>">
                <i class="bi bi-list fs-4" aria-hidden="true"></i>
            </button>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="/" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right me-1"></i><?= e(__('admin.nav.view_site')) ?>
                </a>
                <form method="post" action="/logout" class="m-0">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i><?= e(__('common.nav.logout')) ?>
                    </button>
                </form>
            </div>
        </header>

        <?php if ($isImpersonating): ?>
            <div class="alert alert-warning d-flex justify-content-between align-items-center rounded-0 mb-0" role="alert">
                <span><i class="bi bi-incognito me-2"></i><?= e(__('admin.impersonation.active')) ?></span>
                <form method="post" action="/app/impersonate/stop" class="m-0">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-warning"><?= e(__('admin.impersonation.stop')) ?></button>
                </form>
            </div>
        <?php endif; ?>

        <main class="p-3 p-lg-4">
        <?php $flash = app('session')->getFlash('status'); ?>
        <?php if (!empty($flash)): ?>
            <div class="alert alert-success" role="alert"><?= e($flash) ?></div>
        <?php endif; ?>
        <?= $content ?>
        </main>
    </div>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-label="<?= e(__('admin.nav.dashboard')) ?>">
    <div class="offcanvas-header">
        <span class="fw-bold" style="color: var(--color-primary-700);"><?= e(config_value('system_name', 'LRV Web')) ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?= e(__('common.actions.back')) ?>"></button>
    </div>
    <div class="offcanvas-body">
        <?= $view->partial('layouts.partials.admin-sidebar', get_defined_vars()) ?>
    </div>
</div>

<?= $view->partial('layouts.partials.scripts', get_defined_vars()) ?>
</body>
</html>
