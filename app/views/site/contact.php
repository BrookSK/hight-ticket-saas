<?php
/**
 * Contact page. Contact details come from Configurações Gerais.
 * The primary CTA drives to the waitlist (Phase 1 objective).
 * @var App\Libraries\Translator $t
 */
$whatsapp = whatsapp_link();
?>
<section class="hero">
    <div class="container text-center">
        <span class="eyebrow"><?= e(__('site.nav.contact')) ?></span>
        <h1 class="display-6 fw-bold mt-2"><?= e(__('site.contact.title')) ?></h1>
        <p class="lead text-muted mx-auto" style="max-width:680px;"><?= e(__('site.contact.subtitle')) ?></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <?php if ($email = config_value('site_email')): ?>
                <div class="col-12 col-md-4">
                    <div class="card-surface p-4 text-center h-100">
                        <i class="bi bi-envelope fs-2" style="color:var(--color-primary-600);"></i>
                        <h2 class="h6 mt-3"><?= e(__('site.contact.email')) ?></h2>
                        <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($phone = config_value('site_phone')): ?>
                <div class="col-12 col-md-4">
                    <div class="card-surface p-4 text-center h-100">
                        <i class="bi bi-telephone fs-2" style="color:var(--color-primary-600);"></i>
                        <h2 class="h6 mt-3"><?= e(__('site.contact.phone')) ?></h2>
                        <span><?= e($phone) ?></span>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($whatsapp !== null): ?>
                <div class="col-12 col-md-4">
                    <div class="card-surface p-4 text-center h-100">
                        <i class="bi bi-whatsapp fs-2 text-success"></i>
                        <h2 class="h6 mt-3">WhatsApp</h2>
                        <a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener"><?= e(__('site.contact.whatsapp_cta')) ?></a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-5">
            <a href="/lista-de-espera" class="btn btn-brand btn-lg"><?= e(__('site.cta.waitlist')) ?></a>
        </div>
    </div>
</section>
