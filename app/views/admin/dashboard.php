<?php
/**
 * Admin dashboard: waitlist metrics.
 * @var App\Libraries\Translator $t
 * @var int $total
 * @var int $new
 * @var int $contacted
 * @var int $last7
 * @var int $last30
 * @var array<string,int> $byStatus
 * @var array<int,array<string,mixed>> $topSources
 */
$cards = [
    ['bi-people',        __('admin.dashboard.total'),     $total,     'primary'],
    ['bi-star',          __('admin.dashboard.new'),       $new,       'info'],
    ['bi-telephone',     __('admin.dashboard.contacted'), $contacted, 'success'],
    ['bi-calendar-week', __('admin.dashboard.last7'),     $last7,     'warning'],
    ['bi-calendar-month',__('admin.dashboard.last30'),    $last30,    'secondary'],
];
?>
<div class="mb-4">
    <h1 class="h3 mb-1"><?= e(__('admin.dashboard.title')) ?></h1>
    <?php if (!empty($userName)): ?>
        <p class="text-muted mb-0"><?= e(__('auth.welcome', ['name' => $userName])) ?></p>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($cards as [$icon, $label, $value, $variant]): ?>
        <div class="col-6 col-lg">
            <div class="card-surface p-3 h-100">
                <div class="d-flex align-items-center gap-2 text-muted small">
                    <i class="bi <?= e($icon) ?>" aria-hidden="true"></i><?= e($label) ?>
                </div>
                <div class="h3 mt-2 mb-0"><?= (int) $value ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card-surface p-4 h-100">
            <h2 class="h6 mb-3"><?= e(__('admin.dashboard.by_status')) ?></h2>
            <?php foreach ($byStatus as $status => $count): ?>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span><?= e(__('admin.status.' . $status)) ?></span>
                    <span class="badge-soft-primary px-2"><?= (int) $count ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card-surface p-4 h-100">
            <h2 class="h6 mb-3"><?= e(__('admin.dashboard.top_sources')) ?></h2>
            <?php if ($topSources === []): ?>
                <p class="text-muted mb-0"><?= e(__('common.empty.message')) ?></p>
            <?php else: ?>
                <?php foreach ($topSources as $row): ?>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span><?= e($row['source']) ?></span>
                        <span class="badge-soft-primary px-2"><?= (int) $row['total'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
