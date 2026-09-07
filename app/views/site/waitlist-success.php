<?php
/**
 * Waitlist success page (no invented launch dates).
 * @var App\Libraries\Translator $t
 * @var string|null $message
 */
$shareUrl = 'https://wa.me/?text=' . rawurlencode(__('site.waitlist.share_text'));
?>
<section class="section">
    <div class="container text-center" style="max-width:640px;">
        <div class="card-surface p-5">
            <i class="bi bi-check-circle-fill" style="font-size:3rem;color:var(--color-success);"></i>
            <h1 class="fw-bold mt-3"><?= e(__('site.waitlist.success_title')) ?></h1>
            <p class="text-muted"><?= e($message ?: __('site.waitlist.success_text')) ?></p>

            <div class="text-start mx-auto mt-4" style="max-width:420px;">
                <h2 class="h6"><?= e(__('site.waitlist.next_title')) ?></h2>
                <ul class="text-muted small">
                    <li><?= e(__('site.waitlist.next_1')) ?></li>
                    <li><?= e(__('site.waitlist.next_2')) ?></li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center mt-4">
                <a href="/recursos" class="btn btn-outline-secondary"><?= e(__('site.cta.learn')) ?></a>
                <a href="<?= e($shareUrl) ?>" target="_blank" rel="noopener" class="btn btn-brand">
                    <i class="bi bi-share me-1"></i><?= e(__('site.waitlist.share')) ?>
                </a>
            </div>
        </div>
    </div>
</section>
