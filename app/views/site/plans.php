<?php
/**
 * Plans & pricing page. Content comes from the database (PlanService).
 * @var App\Libraries\Translator $t
 * @var array<int, array<string, mixed>> $plans
 */
$view = app('view');
?>
<section class="hero">
    <div class="container text-center">
        <span class="eyebrow"><?= e(__('site.nav.plans')) ?></span>
        <h1 class="display-6 fw-bold mt-2"><?= e(__('site.plans.title')) ?></h1>
        <p class="lead text-muted mx-auto" style="max-width:720px;"><?= e(__('site.plans.subtitle')) ?></p>
        <p class="text-muted small"><?= e(__('site.plans.free_note')) ?></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?= $view->partial('components.plans-grid', get_defined_vars()) ?>
    </div>
</section>
