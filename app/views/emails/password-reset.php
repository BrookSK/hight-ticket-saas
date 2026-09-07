<?php
/**
 * Password reset email template.
 * Expects: $resetUrl.
 * @var App\Libraries\Translator $t
 */
?>
<h1 style="margin:0 0 16px;font-size:22px;color:#4c1d95;"><?= e(__('mail.reset.title')) ?></h1>
<p style="margin:0 0 12px;font-size:15px;color:#334155;"><?= e(__('mail.reset.body')) ?></p>
<p style="margin:24px 0 0;">
    <a href="<?= e($resetUrl ?? '#') ?>"
       style="display:inline-block;background:#7c3aed;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-weight:600;">
        <?= e(__('mail.reset.cta')) ?>
    </a>
</p>
<p style="margin:16px 0 0;font-size:12px;color:#94a3b8;"><?= e(__('mail.reset.expiry')) ?></p>
