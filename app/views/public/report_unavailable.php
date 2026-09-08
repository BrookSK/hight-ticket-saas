<?php
/**
 * Shown when a report link is not found, expired or revoked.
 * @var App\Libraries\Translator $t
 * @var string $reason
 */
?>
<section class="py-5">
    <div class="container text-center" style="max-width: 640px;">
        <i class="bi bi-link-45deg" style="font-size: 3rem; color: var(--color-primary-700);" aria-hidden="true"></i>
        <h1 class="h3 mt-3"><?= e(__('outreach.public.unavailable_title')) ?></h1>
        <p class="text-muted"><?= e(__('outreach.public.unavailable.' . $reason)) ?></p>
        <a href="/" class="btn btn-outline-secondary mt-2"><?= e(__('common.actions.back_home')) ?></a>
    </div>
</section>
