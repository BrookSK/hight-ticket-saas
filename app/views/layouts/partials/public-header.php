<?php
/**
 * Public site header with responsive navigation.
 * @var App\Libraries\Translator $t
 */
$nav = [
    '/recursos'      => __('site.nav.features'),
    '/como-funciona' => __('site.nav.how'),
    '/planos'        => __('site.nav.plans'),
    '/faq'           => __('site.nav.faq'),
    '/contato'       => __('site.nav.contact'),
];
$logo = config_value('site_logo');
?>
<header class="app-navbar sticky-top">
    <nav class="navbar navbar-expand-lg container-fluid px-3 px-lg-4 h-100">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="/" style="color: var(--color-primary-700);">
            <?php if ($logo): ?>
                <img src="<?= e($logo) ?>" alt="<?= e(config_value('system_name', 'LRV Web')) ?>" height="28">
            <?php else: ?>
                <i class="bi bi-boxes fs-4" aria-hidden="true"></i>
                <span><?= e(config_value('system_name', 'LRV Web')) ?></span>
            <?php endif; ?>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#publicNav" aria-controls="publicNav" aria-expanded="false"
                aria-label="<?= e(__('site.nav.menu')) ?>">
            <i class="bi bi-list fs-3" aria-hidden="true"></i>
        </button>

        <div class="collapse navbar-collapse" id="publicNav">
            <ul class="navbar-nav mx-lg-auto mb-2 mb-lg-0 gap-lg-2">
                <?php foreach ($nav as $href => $label): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= ($activePath ?? '') === $href ? ' active fw-semibold' : '' ?>"
                           href="<?= e($href) ?>"><?= e($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex flex-column flex-lg-row gap-2">
                <a href="/login" class="btn btn-sm btn-outline-secondary"><?= e(__('auth.login.title')) ?></a>
                <a href="/lista-de-espera" class="btn btn-sm btn-brand"><?= e(__('site.cta.waitlist')) ?></a>
            </div>
        </div>
    </nav>
</header>
