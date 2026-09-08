<?php
/**
 * Commercial dashboard.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $metrics
 * @var array<int,array<string,mixed>> $tasks
 * @var list<string> $statuses
 */
$money = static fn (float $v): string => 'R$ ' . number_format($v, 2, ',', '.');
$cards = [
    ['bi-building',   __('crm.dashboard.companies'),   $metrics['companies']],
    ['bi-briefcase',  __('crm.dashboard.leads'),       $metrics['leads_total']],
    ['bi-star',       __('crm.dashboard.new'),         $metrics['leads_new']],
    ['bi-handshake',  __('crm.dashboard.negotiation'), $metrics['leads_negotiation']],
    ['bi-trophy',     __('crm.dashboard.won'),         $metrics['leads_won']],
];
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0"><?= e(__('crm.dashboard.title')) ?></h1>
    <a href="/app/pipeline" class="btn btn-sm btn-brand"><i class="bi bi-kanban me-1"></i><?= e(__('crm.pipeline.title')) ?></a>
</div>

<div class="row g-3 mb-3">
    <?php foreach ($cards as [$icon, $label, $value]): ?>
        <div class="col-6 col-lg">
            <div class="card-surface p-3 h-100">
                <div class="d-flex align-items-center gap-2 text-muted small"><i class="bi <?= e($icon) ?>"></i><?= e($label) ?></div>
                <div class="h3 mt-2 mb-0"><?= (int) $value ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card-surface p-4 h-100">
            <h2 class="h6 mb-3"><?= e(__('crm.dashboard.values')) ?></h2>
            <div class="mb-3">
                <div class="text-muted small"><?= e(__('crm.dashboard.value_estimated')) ?></div>
                <div class="h4"><?= e($money((float) $metrics['value_estimated'])) ?></div>
            </div>
            <div>
                <div class="text-muted small"><?= e(__('crm.dashboard.value_won')) ?></div>
                <div class="h4 text-success"><?= e($money((float) $metrics['value_won'])) ?></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card-surface p-4 h-100">
            <h2 class="h6 mb-3"><?= e(__('crm.dashboard.by_status')) ?></h2>
            <?php foreach ($statuses as $s): ?>
                <?php $count = (int) ($metrics['by_status'][$s] ?? 0); ?>
                <div class="d-flex justify-content-between border-bottom py-1 small">
                    <span><?= e(__('crm.status.' . $s)) ?></span>
                    <span class="badge-soft-primary px-2"><?= $count ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card-surface p-4 h-100">
            <h2 class="h6 mb-3"><?= e(__('crm.dashboard.pending_tasks')) ?></h2>
            <?php if ($tasks === []): ?>
                <p class="text-muted small mb-0"><?= e(__('crm.dashboard.no_tasks')) ?></p>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="border-bottom py-2 small">
                        <?php if (!empty($task['lead_id'])): ?>
                            <a href="/app/leads/<?= (int) $task['lead_id'] ?>" class="text-decoration-none fw-semibold"><?= e($task['title'] ?: __('crm.activity_type.task')) ?></a>
                        <?php else: ?>
                            <span class="fw-semibold"><?= e($task['title'] ?: __('crm.activity_type.task')) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($task['scheduled_at'])): ?><div class="text-muted" style="font-size:.72rem;"><?= e($task['scheduled_at']) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
