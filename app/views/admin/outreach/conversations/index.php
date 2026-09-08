<?php
/**
 * Conversations inbox.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $conversations
 * @var array<string,mixed> $filters
 */
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div><h1 class="h3 mb-1"><?= e(__('outreach.conversations.title')) ?></h1></div>
    <form method="get" action="/app/outreach/conversations" class="d-flex gap-2">
        <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
            <option value=""><?= e(__('admin.waitlist.all')) ?></option>
            <?php foreach (['open', 'awaiting_seller', 'replied', 'closed'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(__('outreach.conversations.status.' . $s)) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="card-surface">
    <?php if ($conversations === []): ?>
        <div class="empty-state">
            <i class="bi bi-chat-dots empty-state__icon"></i>
            <h2 class="h6"><?= e(__('outreach.conversations.empty_title')) ?></h2>
            <p class="mb-0"><?= e(__('outreach.conversations.empty_text')) ?></p>
        </div>
    <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($conversations as $c): ?>
                <a href="/app/outreach/conversations/<?= (int) $c['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi <?= $c['channel'] === 'email' ? 'bi-envelope' : 'bi-whatsapp' ?> me-2"></i>
                        <span class="fw-semibold"><?= e((string) ($c['peer_address'] ?? '—')) ?></span>
                        <?php if (!empty($c['needs_human'])): ?><span class="badge bg-danger ms-2"><?= e(__('outreach.conversations.needs_human')) ?></span><?php endif; ?>
                    </span>
                    <span class="text-muted small"><span class="badge-soft-primary px-2 me-2"><?= e(__('outreach.conversations.status.' . $c['status'])) ?></span><?= e((string) ($c['last_message_at'] ?? '')) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
