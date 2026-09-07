<?php
/**
 * Features page — categorized platform capabilities.
 * Future/unavailable capabilities are clearly labeled "in development".
 * @var App\Libraries\Translator $t
 */
$categories = [
    ['icon' => 'bi-search',          'key' => 'prospecting', 'future' => true],
    ['icon' => 'bi-clipboard-check', 'key' => 'audit',       'future' => true],
    ['icon' => 'bi-kanban',          'key' => 'sales',       'future' => true],
    ['icon' => 'bi-diagram-3',       'key' => 'operations',  'future' => true],
    ['icon' => 'bi-magic',           'key' => 'automation',  'future' => true],
    ['icon' => 'bi-robot',           'key' => 'ai',          'future' => true],
    ['icon' => 'bi-hdd-network',     'key' => 'hosting',     'future' => true],
    ['icon' => 'bi-activity',        'key' => 'maintenance', 'future' => true],
];
?>
<section class="hero">
    <div class="container text-center">
        <span class="eyebrow"><?= e(__('site.nav.features')) ?></span>
        <h1 class="display-6 fw-bold mt-2"><?= e(__('site.features.title')) ?></h1>
        <p class="lead text-muted mx-auto" style="max-width:720px;"><?= e(__('site.features.subtitle')) ?></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($categories as $cat): ?>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card-surface p-4 h-100">
                        <i class="bi <?= e($cat['icon']) ?> fs-2" style="color:var(--color-primary-600);"></i>
                        <h2 class="h5 mt-3 d-flex align-items-center gap-2">
                            <?= e(__('site.features.' . $cat['key'] . '_title')) ?>
                            <?php if ($cat['future']): ?>
                                <span class="badge text-bg-light border" style="font-size:0.65rem;"><?= e(__('site.features.soon')) ?></span>
                            <?php endif; ?>
                        </h2>
                        <p class="text-muted mb-0"><?= e(__('site.features.' . $cat['key'] . '_text')) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="text-center text-muted small mt-4"><?= e(__('site.features.disclaimer')) ?></p>
    </div>
</section>
