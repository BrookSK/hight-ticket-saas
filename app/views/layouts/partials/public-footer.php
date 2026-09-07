<?php
/**
 * Public site footer with columns and social links.
 * @var App\Libraries\Translator $t
 */
$socials = array_filter([
    'instagram' => config_value('social_instagram'),
    'facebook'  => config_value('social_facebook'),
    'linkedin'  => config_value('social_linkedin'),
    'youtube'   => config_value('social_youtube'),
]);
$socialIcons = [
    'instagram' => 'bi-instagram',
    'facebook'  => 'bi-facebook',
    'linkedin'  => 'bi-linkedin',
    'youtube'   => 'bi-youtube',
];
?>
<footer class="border-top mt-6" style="background: var(--color-surface);">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="d-flex align-items-center gap-2 fw-bold mb-2" style="color: var(--color-primary-700);">
                    <i class="bi bi-boxes fs-4" aria-hidden="true"></i>
                    <span><?= e(config_value('system_name', 'LRV Web')) ?></span>
                </div>
                <p class="text-muted"><?= e(config_value('site_description', __('common.app_tagline'))) ?></p>
                <?php if ($socials !== []): ?>
                    <div class="d-flex gap-3 fs-5 mt-3">
                        <?php foreach ($socials as $key => $url): ?>
                            <a href="<?= e($url) ?>" target="_blank" rel="noopener"
                               aria-label="<?= e(ucfirst($key)) ?>">
                                <i class="bi <?= e($socialIcons[$key] ?? 'bi-link') ?>" aria-hidden="true"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-6 col-lg-2">
                <h2 class="h6"><?= e(__('site.footer.product')) ?></h2>
                <ul class="list-unstyled">
                    <li><a class="text-muted" href="/recursos"><?= e(__('site.nav.features')) ?></a></li>
                    <li><a class="text-muted" href="/como-funciona"><?= e(__('site.nav.how')) ?></a></li>
                    <li><a class="text-muted" href="/planos"><?= e(__('site.nav.plans')) ?></a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h2 class="h6"><?= e(__('site.footer.resources')) ?></h2>
                <ul class="list-unstyled">
                    <li><a class="text-muted" href="/faq"><?= e(__('site.nav.faq')) ?></a></li>
                    <li><a class="text-muted" href="/contato"><?= e(__('site.nav.contact')) ?></a></li>
                    <li><a class="text-muted" href="/lista-de-espera"><?= e(__('site.cta.waitlist')) ?></a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h2 class="h6"><?= e(__('site.footer.legal')) ?></h2>
                <ul class="list-unstyled">
                    <li><a class="text-muted" href="/termos-de-uso"><?= e(__('site.footer.terms')) ?></a></li>
                    <li><a class="text-muted" href="/politica-de-privacidade"><?= e(__('site.footer.privacy')) ?></a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h2 class="h6"><?= e(__('site.footer.contact')) ?></h2>
                <ul class="list-unstyled text-muted small">
                    <?php if ($email = config_value('site_email')): ?>
                        <li><i class="bi bi-envelope me-1"></i><?= e($email) ?></li>
                    <?php endif; ?>
                    <?php if ($phone = config_value('site_phone')): ?>
                        <li><i class="bi bi-telephone me-1"></i><?= e($phone) ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="border-top py-3 text-center text-muted">
        <small>&copy; <?= date('Y') ?> <?= e(config_value('system_name', 'LRV Web')) ?>. <?= e(__('site.footer.rights')) ?></small>
    </div>
</footer>
