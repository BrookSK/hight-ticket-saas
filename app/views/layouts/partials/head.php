<?php
/**
 * Shared <head> partial with SEO + analytics.
 *
 * Expects (all optional): $title, $metaDescription, $canonical, $ogImage.
 * SEO defaults and analytics IDs come from Configurações Gerais (never hardcoded).
 * @var App\Libraries\Translator $t
 */
$systemName = config_value('system_name', 'LRV Web');
$pageTitle = isset($title) && $title !== ''
    ? $title . ' — ' . $systemName
    : config_value('seo_title', $systemName);
$description = $metaDescription ?? config_value('seo_description', __('common.app_tagline'));
$ogImage = $ogImage ?? config_value('seo_og_image');
$gaId = config_value('analytics_ga_id');
$gtmId = config_value('analytics_gtm_id');
$clarityId = config_value('analytics_clarity_id');
$googleVerification = config_value('seo_google_verification');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="robots" content="index, follow">
<?php if (!empty($canonical)): ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>
<?php if (!empty($googleVerification)): ?>
<meta name="google-site-verification" content="<?= e($googleVerification) ?>">
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

<meta name="csrf-token" content="<?= e($csrfToken ?? '') ?>">

<!-- Bootstrap 5 + Bootstrap Icons (single icon library) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="/assets/css/tokens.css" rel="stylesheet">
<link href="/assets/css/app.css" rel="stylesheet">

<?php if (!empty($gtmId)): ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e($gtmId) ?>');</script>
<?php endif; ?>
<?php if (!empty($gaId)): ?>
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($gaId) ?>');</script>
<?php endif; ?>
<?php if (!empty($clarityId)): ?>
<!-- Microsoft Clarity -->
<script>(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,"clarity","script","<?= e($clarityId) ?>");</script>
<?php endif; ?>
