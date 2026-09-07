<?php
/**
 * Institutional home (main commercial page).
 * @var App\Core\View $this
 * @var App\Libraries\Translator $t
 * @var array<int, array<string, mixed>> $plans
 */
$view = app('view');
?>
<!-- Hero -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-12 col-lg-6">
                <span class="eyebrow"><?= e(__('common.app_tagline')) ?></span>
                <h1 class="display-5 fw-bold mt-2 mb-3"><?= e(__('site.home.hero_title')) ?></h1>
                <p class="lead text-muted mb-4"><?= e(__('site.home.hero_subtitle')) ?></p>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <a href="/lista-de-espera" class="btn btn-brand btn-lg"><?= e(__('site.cta.waitlist')) ?></a>
                    <a href="/como-funciona" class="btn btn-outline-secondary btn-lg"><?= e(__('site.cta.learn')) ?></a>
                </div>
                <p class="text-muted small mt-3"><i class="bi bi-shield-check me-1"></i><?= e(__('site.home.hero_note')) ?></p>
            </div>
            <div class="col-12 col-lg-6">
                <?= $view->partial('components.mockup-dashboard', get_defined_vars()) ?>
            </div>
        </div>
    </div>
</section>

<!-- Problem -->
<section class="section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold"><?= e(__('site.home.problem_title')) ?></h2>
            <p class="text-muted mx-auto" style="max-width:680px;"><?= e(__('site.home.problem_text')) ?></p>
        </div>
        <div class="row g-3 justify-content-center">
            <?php foreach (['bi-search', 'bi-clipboard-data', 'bi-kanban', 'bi-file-earmark-text', 'bi-hdd-network', 'bi-chat-dots'] as $i => $icon): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card-surface p-3 text-center h-100">
                        <i class="bi <?= e($icon) ?> fs-3" style="color:var(--color-primary-500);"></i>
                        <p class="small text-muted mb-0 mt-2"><?= e(__('site.home.problem_tool_' . ($i + 1))) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Solution / promise -->
<section class="section" style="background:var(--color-bg-subtle);">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-12 col-lg-6 order-lg-2">
                <h2 class="fw-bold mb-3"><?= e(__('site.home.solution_title')) ?></h2>
                <p class="text-muted mb-4"><?= e(__('site.home.solution_text')) ?></p>
                <ul class="list-unstyled">
                    <?php foreach (['promise_1', 'promise_2', 'promise_3', 'promise_4', 'promise_5'] as $key): ?>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><?= e(__('site.home.' . $key)) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-12 col-lg-6 order-lg-1">
                <?= $view->partial('components.mockup-dashboard', get_defined_vars()) ?>
            </div>
        </div>
    </div>
</section>

<!-- Flow -->
<section class="section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold"><?= e(__('site.home.flow_title')) ?></h2>
            <p class="text-muted"><?= e(__('site.home.flow_text')) ?></p>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <?php
            $flow = ['prospect', 'audit', 'crm', 'proposal', 'project', 'hosting', 'maintenance', 'reports'];
            foreach ($flow as $index => $step): ?>
                <span class="badge-soft-primary px-3 py-2"><?= e(__('site.flow.' . $step)) ?></span>
                <?php if ($index < count($flow) - 1): ?>
                    <i class="bi bi-arrow-right align-self-center text-muted" aria-hidden="true"></i>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Plans preview -->
<section class="section" style="background:var(--color-bg-subtle);">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold"><?= e(__('site.plans.title')) ?></h2>
            <p class="text-muted"><?= e(__('site.plans.subtitle')) ?></p>
        </div>
        <?= $view->partial('components.plans-grid', get_defined_vars()) ?>
    </div>
</section>

<!-- CTA -->
<section class="section">
    <div class="container">
        <div class="card-surface p-5 text-center" style="background:var(--gradient-brand);color:#fff;">
            <h2 class="fw-bold mb-2"><?= e(__('site.home.cta_title')) ?></h2>
            <p class="mb-4"><?= e(__('site.home.cta_text')) ?></p>
            <a href="/lista-de-espera" class="btn btn-light btn-lg fw-semibold"><?= e(__('site.cta.waitlist')) ?></a>
        </div>
    </div>
</section>
