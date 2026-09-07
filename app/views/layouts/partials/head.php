<?php
/**
 * Shared <head> partial.
 *
 * Expects (all optional): $title, $metaDescription, $canonical, $ogImage.
 * SEO tags are rendered here so institutional pages inherit them consistently.
 * @var App\Libraries\Translator $t
 */
$pageTitle = isset($title) && $title !== '' ? $title : __('common.app_name');
$description = $metaDescription ?? __('common.app_tagline');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="robots" content="index, follow">
<?php if (!empty($canonical)): ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<?php if (!empty($ogImage)): ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php endif; ?>

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($description) ?>">

<!-- CSRF token available to scripts -->
<meta name="csrf-token" content="<?= e($csrfToken ?? '') ?>">

<!-- Bootstrap 5 + Bootstrap Icons (single icon library) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="/assets/css/tokens.css" rel="stylesheet">
<link href="/assets/css/app.css" rel="stylesheet">
