<?php
/**
 * Waitlist form (simple: name/email/phone required; others optional).
 * Includes CSRF, honeypot and time-trap anti-spam, UTM capture and consent.
 * @var App\Libraries\Translator $t
 * @var array<string, string> $errors
 * @var array<string, mixed> $old
 * @var int $formStarted
 */
$errors = $errors ?? [];
$old = $old ?? [];
$val = static fn (string $k): string => e((string) ($old[$k] ?? ''));
$err = static fn (string $k): string => isset($errors[$k]) ? ' is-invalid' : '';
?>
<section class="hero">
    <div class="container text-center">
        <span class="eyebrow"><?= e(__('common.app_tagline')) ?></span>
        <h1 class="display-6 fw-bold mt-2"><?= e(__('site.waitlist.title')) ?></h1>
        <p class="lead text-muted mx-auto" style="max-width:640px;"><?= e(__('site.waitlist.subtitle')) ?></p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:640px;">
        <div class="card-surface p-4 p-md-5">
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger" role="alert"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <form method="post" action="/lista-de-espera" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="form_started" value="<?= (int) ($formStarted ?? time()) ?>">
                <input type="hidden" name="source_url" id="wl_source_url" value="">
                <input type="hidden" name="utm_source" id="wl_utm_source" value="">
                <input type="hidden" name="utm_medium" id="wl_utm_medium" value="">
                <input type="hidden" name="utm_campaign" id="wl_utm_campaign" value="">
                <input type="hidden" name="utm_content" id="wl_utm_content" value="">
                <input type="hidden" name="utm_term" id="wl_utm_term" value="">

                <!-- Honeypot (hidden from users) -->
                <div class="d-none" aria-hidden="true">
                    <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label"><?= e(__('site.waitlist.field_name')) ?> *</label>
                    <input type="text" class="form-control<?= $err('name') ?>" id="name" name="name" value="<?= $val('name') ?>" required>
                    <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label for="email" class="form-label"><?= e(__('site.waitlist.field_email')) ?> *</label>
                        <input type="email" class="form-control<?= $err('email') ?>" id="email" name="email" value="<?= $val('email') ?>" required>
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label for="phone" class="form-label"><?= e(__('site.waitlist.field_phone')) ?> *</label>
                        <input type="tel" class="form-control<?= $err('phone') ?>" id="phone" name="phone" value="<?= $val('phone') ?>" required>
                        <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label for="company" class="form-label"><?= e(__('site.waitlist.field_company')) ?></label>
                        <input type="text" class="form-control" id="company" name="company" value="<?= $val('company') ?>">
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label for="sites_quantity" class="form-label"><?= e(__('site.waitlist.field_sites')) ?></label>
                        <select class="form-select" id="sites_quantity" name="sites_quantity">
                            <option value=""><?= e(__('site.waitlist.select')) ?></option>
                            <option value="1-5">1–5</option>
                            <option value="6-20">6–20</option>
                            <option value="21-50">21–50</option>
                            <option value="50+">50+</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="main_service" class="form-label"><?= e(__('site.waitlist.field_service')) ?></label>
                    <input type="text" class="form-control" id="main_service" name="main_service" value="<?= $val('main_service') ?>">
                </div>

                <div class="mb-3">
                    <label for="message" class="form-label"><?= e(__('site.waitlist.field_message')) ?></label>
                    <textarea class="form-control" id="message" name="message" rows="3"><?= $val('message') ?></textarea>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input<?= $err('consent') ?>" type="checkbox" id="consent" name="consent" value="1" required>
                    <label class="form-check-label small" for="consent">
                        <?= e(__('site.waitlist.consent')) ?>
                        <a href="/politica-de-privacidade" target="_blank" rel="noopener"><?= e(__('site.footer.privacy')) ?></a>.
                    </label>
                </div>

                <button type="submit" class="btn btn-brand btn-lg w-100"><?= e(__('site.waitlist.submit')) ?></button>
            </form>
        </div>
    </div>
</section>

<script>
// Capture UTM parameters and the source URL into hidden fields (client-side).
(function () {
    var params = new URLSearchParams(window.location.search);
    ['utm_source','utm_medium','utm_campaign','utm_content','utm_term'].forEach(function (k) {
        var el = document.getElementById('wl_' + k);
        if (el) { el.value = params.get(k) || ''; }
    });
    var src = document.getElementById('wl_source_url');
    if (src) { src.value = document.referrer || window.location.href; }
})();
</script>
