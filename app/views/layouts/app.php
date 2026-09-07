<?php
/**
 * Administrative (app) layout.
 *
 * Wraps authenticated back-office pages with a responsive sidebar + navbar.
 * Mobile First: the sidebar collapses into an offcanvas on small screens.
 * Page content arrives in $content.
 *
 * @var string $content
 * @var App\Libraries\Translator $t
 */
$view = app('view');
?><!doctype html>
<html lang="<?= e($locale ?? 'pt-BR') ?>">
<head>
<?= $view->partial('layouts.partials.head', get_defined_vars()) ?>
</head>
<body>
<div class="d-flex">
    <!-- Sidebar (offcanvas on mobile) -->
    <nav class="app-sidebar d-none d-lg-block p-3" aria-label="<?= e(__('common.nav.dashboard')) ?>">
        <a href="/" class="d-flex align-items-center gap-2 fw-bold mb-4" style="color: var(--color-primary-700);">
            <i class="bi bi-boxes fs-4" aria-hidden="true"></i>
            <span><?= e(__('common.app_name')) ?></span>
        </a>
        <ul class="nav nav-pills flex-column gap-1">
            <li class="nav-item">
                <a class="nav-link active" href="/app">
                    <i class="bi bi-speedometer2 me-2" aria-hidden="true"></i><?= e(__('common.nav.dashboard')) ?>
                </a>
            </li>
        </ul>
    </nav>

    <div class="flex-grow-1 min-vw-0">
        <!-- Navbar -->
        <header class="app-navbar d-flex align-items-center px-3 px-lg-4">
            <button class="btn btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#mobileSidebar" aria-controls="mobileSidebar"
                    aria-label="<?= e(__('common.nav.dashboard')) ?>">
                <i class="bi bi-list fs-4" aria-hidden="true"></i>
            </button>
            <div class="ms-auto d-flex align-items-center gap-3">
                <a href="/logout" class="btn btn-sm btn-outline-secondary"
                   data-confirm="<?= e(__('auth.logout')) ?>">
                    <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i><?= e(__('common.nav.logout')) ?>
                </a>
            </div>
        </header>

        <main class="p-3 p-lg-4">
        <?= $content ?>
        </main>
    </div>
</div>

<!-- Mobile sidebar -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-label="<?= e(__('common.nav.dashboard')) ?>">
    <div class="offcanvas-header">
        <span class="fw-bold" style="color: var(--color-primary-700);"><?= e(__('common.app_name')) ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="nav nav-pills flex-column gap-1">
            <li class="nav-item">
                <a class="nav-link active" href="/app">
                    <i class="bi bi-speedometer2 me-2" aria-hidden="true"></i><?= e(__('common.nav.dashboard')) ?>
                </a>
            </li>
        </ul>
    </div>
</div>

<?= $view->partial('layouts.partials.scripts', get_defined_vars()) ?>
</body>
</html>
