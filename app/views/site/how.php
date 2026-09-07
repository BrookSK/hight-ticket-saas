<?php
/**
 * How it works — conceptual journey (Phase 1: presentation only).
 * @var App\Libraries\Translator $t
 */
$steps = range(1, 10);
?>
<section class="hero">
    <div class="container text-center">
        <span class="eyebrow"><?= e(__('site.nav.how')) ?></span>
        <h1 class="display-6 fw-bold mt-2"><?= e(__('site.how.title')) ?></h1>
        <p class="lead text-muted mx-auto" style="max-width:720px;"><?= e(__('site.how.subtitle')) ?></p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:760px;">
        <?php foreach ($steps as $n): ?>
            <div class="journey-step">
                <div class="journey-step__num"><?= (int) $n ?></div>
                <div>
                    <h2 class="h6 mb-1"><?= e(__('site.how.step_' . $n . '_title')) ?></h2>
                    <p class="text-muted mb-0"><?= e(__('site.how.step_' . $n . '_text')) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="text-center mt-4">
            <a href="/lista-de-espera" class="btn btn-brand btn-lg"><?= e(__('site.cta.waitlist')) ?></a>
        </div>
    </div>
</section>
