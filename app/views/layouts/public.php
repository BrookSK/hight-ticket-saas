<?php
/**
 * Institutional (public) layout.
 *
 * Wraps public/marketing pages with SEO head, responsive header, footer and
 * floating actions (WhatsApp + Help). Page content arrives in $content.
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
<?= $view->partial('layouts.partials.public-header', get_defined_vars()) ?>

<main>
<?= $content ?>
</main>

<?= $view->partial('layouts.partials.public-footer', get_defined_vars()) ?>
<?= $view->partial('components.floating-actions', get_defined_vars()) ?>
<?= $view->partial('layouts.partials.scripts', get_defined_vars()) ?>
</body>
</html>
