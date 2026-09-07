<?php
/**
 * Waitlist confirmation email template.
 *
 * Rendered by EmailTemplateService inside the base email layout. All text is
 * translatable. Expects: $name, $appName, $siteUrl.
 * @var App\Libraries\Translator $t
 */
?>
<h1 style="margin:0 0 16px;font-size:22px;color:#4c1d95;"><?= e(__('mail.waitlist.title')) ?></h1>
<p style="margin:0 0 12px;font-size:15px;color:#0f172a;">
    <?= e(__('mail.waitlist.greeting', ['name' => $name ?? ''])) ?>
</p>
<p style="margin:0 0 12px;font-size:15px;color:#334155;">
    <?= e(__('mail.waitlist.body')) ?>
</p>
<p style="margin:24px 0 0;">
    <a href="<?= e($siteUrl ?? '/') ?>"
       style="display:inline-block;background:#7c3aed;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-weight:600;">
        <?= e(__('mail.waitlist.cta')) ?>
    </a>
</p>
