<?php
/**
 * Floating actions component: WhatsApp + Help buttons.
 *
 * Present across the institutional site. The Help button is prepared to later
 * integrate with AI; initially it can open a FAQ or contact form.
 *
 * Expects (optional): $whatsappUrl, $helpUrl.
 */
$whatsappUrl = $whatsappUrl ?? '#';
$helpUrl = $helpUrl ?? '#';
?>
<div class="floating-actions" aria-label="<?= e(__('common.actions.help')) ?>">
    <a href="<?= e($whatsappUrl) ?>" class="floating-btn floating-btn--whatsapp"
       target="_blank" rel="noopener" aria-label="WhatsApp">
        <i class="bi bi-whatsapp" aria-hidden="true"></i>
    </a>
    <a href="<?= e($helpUrl) ?>" class="floating-btn floating-btn--help"
       aria-label="<?= e(__('common.actions.help')) ?>">
        <i class="bi bi-question-lg" aria-hidden="true"></i>
    </a>
</div>
