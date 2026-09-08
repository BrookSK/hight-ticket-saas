<?php
/**
 * Conversation thread with message timeline.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $thread
 * @var array<int,array<string,mixed>> $messages
 */
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e((string) ($thread['peer_address'] ?? '')) ?></h1>
        <p class="text-muted mb-0"><span class="badge-soft-primary px-2"><?= e(__('outreach.conversations.status.' . $thread['status'])) ?></span></p>
    </div>
    <div class="d-flex gap-2">
        <?php if (!empty($thread['lead_id'])): ?><a href="/app/leads/<?= (int) $thread['lead_id'] ?>" class="btn btn-outline-secondary"><i class="bi bi-briefcase me-1"></i><?= e(__('outreach.conversations.open_lead')) ?></a><?php endif; ?>
        <?php if ($thread['status'] !== 'closed' && can('outreach.update')): ?>
            <form method="post" action="/app/outreach/conversations/<?= (int) $thread['id'] ?>/close" data-confirm="<?= e(__('outreach.conversations.confirm_close')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger"><i class="bi bi-check2-circle me-1"></i><?= e(__('outreach.conversations.close')) ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card-surface p-3">
    <?php if ($messages === []): ?>
        <p class="text-muted mb-0"><?= e(__('outreach.conversations.no_messages')) ?></p>
    <?php else: ?>
        <?php foreach ($messages as $m): ?>
            <?php $inbound = ($m['direction'] ?? 'outbound') === 'inbound'; ?>
            <div class="d-flex mb-3 <?= $inbound ? '' : 'justify-content-end' ?>">
                <div class="p-2 rounded <?= $inbound ? 'bg-light' : 'badge-soft-primary' ?>" style="max-width: 75%;">
                    <div class="small text-muted mb-1"><?= e($inbound ? __('outreach.conversations.them') : __('outreach.conversations.you')) ?> · <?= e((string) ($m['created_at'] ?? '')) ?><?php if (!$inbound): ?> · <?= e(__('outreach.status.' . $m['status'])) ?><?php endif; ?></div>
                    <div style="white-space:pre-wrap;"><?= e((string) $m['body']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
