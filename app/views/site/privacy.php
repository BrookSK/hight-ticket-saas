<?php
/**
 * Privacy Policy (LGPD-aware, plain layout).
 * @var App\Libraries\Translator $t
 */
?>
<section class="section">
    <div class="container" style="max-width:820px;">
        <h1 class="fw-bold mb-4"><?= e(__('site.footer.privacy')) ?></h1>
        <p class="text-muted"><?= e(__('legal.updated', ['date' => date('d/m/Y')])) ?></p>
        <?php foreach (range(1, 6) as $i): ?>
            <h2 class="h5 mt-4"><?= e(__('legal.privacy.s' . $i . '_title')) ?></h2>
            <p class="text-muted"><?= e(__('legal.privacy.s' . $i . '_text')) ?></p>
        <?php endforeach; ?>
    </div>
</section>
