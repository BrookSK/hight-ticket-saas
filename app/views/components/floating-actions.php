<?php
/**
 * Floating actions component: WhatsApp + Help buttons.
 *
 * The WhatsApp number and message come from Configurações Gerais (never
 * hardcoded). The Help button opens the help/FAQ area and is prepared for
 * future AI integration.
 * @var App\Libraries\Translator $t
 */
$whatsapp = whatsapp_link();
$helpUrl = $helpUrl ?? '/faq';
?>
<div class="floating-actions" aria-label="<?= e(__('common.actions.help')) ?>">
    <?php if ($whatsapp !== null): ?>
        <a href="<?= e($whatsapp) ?>" class="floating-btn floating-btn--whatsapp"
           target="_blank" rel="noopener" aria-label="WhatsApp">
            <i class="bi bi-whatsapp" aria-hidden="true"></i>
        </a>
    <?php endif; ?>
    <a href="<?= e($helpUrl) ?>" class="floating-btn floating-btn--help"
       aria-label="<?= e(__('common.actions.help')) ?>">
        <i class="bi bi-question-lg" aria-hidden="true"></i>
    </a>
</div>
