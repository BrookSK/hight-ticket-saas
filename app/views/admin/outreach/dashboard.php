<?php
/**
 * Outreach seller dashboard.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $metrics
 * @var array{open:bool,next:string} $window
 * @var bool $requiresApproval
 * @var array<int,array<string,mixed>> $tasks
 */
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e(__('outreach.dashboard.title')) ?></h1>
        <p class="text-muted mb-0"><?= e(__('outreach.dashboard.subtitle')) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="/app/outreach/outbox" class="btn btn-outline-secondary"><i class="bi bi-inbox me-1"></i><?= e(__('outreach.outbox.title')) ?></a>
        <a href="/app/outreach/conversations" class="btn btn-outline-secondary"><i class="bi bi-chat-dots me-1"></i><?= e(__('outreach.conversations.title')) ?></a>
    </div>
</div>

<?php if (!$window['open']): ?>
    <div class="alert alert-info d-flex align-items-center" role="alert">
        <i class="bi bi-clock me-2"></i>
        <span><?= e(__('outreach.dashboard.window_closed', ['time' => $window['next']])) ?></span>
    </div>
<?php endif; ?>
<?php if ($requiresApproval): ?>
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="bi bi-person-check me-2"></i>
        <span><?= e(__('outreach.dashboard.approval_on')) ?></span>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['outreach.metrics.sent', (int) ($metrics['sent'] ?? 0), 'bi-send', 'text-primary'],
        ['outreach.metrics.delivered', (int) ($metrics['delivered'] ?? 0), 'bi-check2-all', 'text-info'],
        ['outreach.metrics.read', (int) ($metrics['read'] ?? 0), 'bi-eye', 'text-success'],
        ['outreach.metrics.pending', (int) ($metrics['pending'] ?? 0), 'bi-hourglass-split', 'text-warning'],
        ['outreach.metrics.failed', (int) ($metrics['failed'] ?? 0), 'bi-exclamation-triangle', 'text-danger'],
    ];
    foreach ($cards as [$label, $value, $icon, $color]): ?>
        <div class="col-6 col-lg">
            <div class="card-surface p-3 h-100">
                <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                    <i class="bi <?= e($icon) ?> <?= e($color) ?>"></i><?= e(__($label)) ?>
                </div>
                <div class="h4 mb-0"><?= $value ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card-surface p-3 h-100">
            <h2 class="h6 mb-3"><?= e(__('outreach.metrics.rates')) ?></h2>
            <p class="mb-1"><?= e(__('outreach.metrics.delivery_rate')) ?>: <strong><?= e((string) ($metrics['delivery_rate'] ?? 0)) ?>%</strong></p>
            <p class="mb-0"><?= e(__('outreach.metrics.read_rate')) ?>: <strong><?= e((string) ($metrics['read_rate'] ?? 0)) ?>%</strong></p>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card-surface p-3 h-100">
            <h2 class="h6 mb-3"><?= e(__('outreach.dashboard.tasks')) ?></h2>
            <?php if ($tasks === []): ?>
                <p class="text-muted mb-0"><?= e(__('outreach.dashboard.no_tasks')) ?></p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($tasks as $task): ?>
                        <li class="d-flex justify-content-between border-bottom py-2">
                            <span><?= e((string) $task['title']) ?></span>
                            <span class="text-muted small"><?= e((string) ($task['due_at'] ?? '')) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
