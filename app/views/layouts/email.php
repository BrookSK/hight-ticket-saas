<?php
/**
 * Base email layout (reusable). Wraps email content with consistent branding.
 * Resolved as layouts.email by the View renderer.
 *
 * Expects: $content (HTML), $appName.
 * @var App\Libraries\Translator $t
 */
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($appName ?? 'LRV Web') ?></title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                   style="width:600px;max-width:100%;background:#ffffff;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="background:linear-gradient(135deg,#7c3aed,#a78bfa);padding:20px 32px;">
                        <span style="color:#ffffff;font-size:18px;font-weight:700;"><?= e($appName ?? 'LRV Web') ?></span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <?= $content ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 32px;background:#f8fafc;color:#64748b;font-size:12px;">
                        &copy; <?= date('Y') ?> <?= e($appName ?? 'LRV Web') ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
