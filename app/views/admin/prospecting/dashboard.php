<?php
/**
 * Prospecting dashboard.
 * @var App\Libraries\Translator $t
 * @var int $campaigns @var int $newOpportunities @var int $hotOpportunities @var int $converted
 */
$cards = [
    ['bi-megaphone',   __('prospecting.dashboard.campaigns'), $campaigns, '/app/prospecting/campaigns'],
    ['bi-stars',       __('prospecting.dashboard.new_opps'), $newOpportunities, '/app/prospecting/review'],
    ['bi-fire',        __('prospecting.dashboard.hot_opps'), $hotOpportunities, '/app/prospecting/review?priority=high'],
    ['bi-briefcase',   __('prospecting.dashboard.converted'), $converted, '/app/prospecting/review?status=converted'],
];
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0"><?= e(__('prospecting.dashboard.title')) ?></h1>
    <?php if (can('prospecting.create')): ?>
        <a href="/app/prospecting/campaigns/create" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i><?= e(__('prospecting.campaigns.new')) ?></a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($cards as [$icon, $label, $value, $href]): ?>
        <div class="col-6 col-lg-3">
            <a href="<?= e($href) ?>" class="text-decoration-none">
                <div class="card-surface p-3 h-100">
                    <div class="d-flex align-items-center gap-2 text-muted small"><i class="bi <?= e($icon) ?>"></i><?= e($label) ?></div>
                    <div class="h3 mt-2 mb-0"><?= (int) $value ?></div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="card-surface p-4">
    <h2 class="h6 mb-2"><?= e(__('prospecting.dashboard.how_title')) ?></h2>
    <p class="text-muted small mb-3"><?= e(__('prospecting.dashboard.how_text')) ?></p>
    <div class="d-flex flex-wrap gap-2">
        <a href="/app/prospecting/campaigns/create" class="btn btn-sm btn-brand"><?= e(__('prospecting.campaigns.new')) ?></a>
        <a href="/app/prospecting/review" class="btn btn-sm btn-outline-secondary"><?= e(__('prospecting.review.title')) ?></a>
        <a href="/app/prospecting/exclusions" class="btn btn-sm btn-outline-secondary"><?= e(__('prospecting.exclusion.title')) ?></a>
    </div>
</div>
