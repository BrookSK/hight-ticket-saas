<?php
/**
 * Institutional (public) layout.
 *
 * Wraps public/marketing pages. Includes SEO head, floating actions
 * (WhatsApp + Help) and shared scripts. Page content arrives in $content.
 *
 * @var App\Core\View $this
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
<header class="app-navbar d-flex align-items-center px-4">
    <a href="/" class="d-flex align-items-center gap-2 fw-bold" style="color: var(--color-primary-700);">
        <i class="bi bi-boxes fs-4" aria-hidden="true"></i>
        <span><?= e(__('common.app_name')) ?></span>
    </a>
    <nav class="ms-auto d-flex align-items-center gap-3">
        <a href="/login" class="btn btn-sm btn-brand"><?= e(__('auth.login.title')) ?></a>
    </nav>
</header>

<main>
<?= $content ?>
</main>

<footer class="border-top mt-5 py-4 text-center text-muted">
    <small>&copy; <?= date('Y') ?> <?= e(__('common.app_name')) ?> — <?= e(__('common.app_tagline')) ?></small>
</footer>

<?= $view->partial('components.floating-actions', get_defined_vars()) ?>
<?= $view->partial('layouts.partials.scripts', get_defined_vars()) ?>
</body>
</html>
