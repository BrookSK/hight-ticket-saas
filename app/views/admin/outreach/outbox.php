<?php
/**
 * Outreach outbox with status filters and approval/cancel actions.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $messages
 * @var array<string,mixed> $filters
 * @var int $page @var int $pages @var int $total
 */
$qs = static fn (array $extra): string => '?' . http_build_query(array_merge(array_filter($filters, static fn ($v) => $v !== '' && $v !== null), $extra));
$statuses = ['draft', 'pending_approval', 'scheduled', 'sending', 'sent', 'delivered', 'read', 'failed', 'cancelled'];
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e(__('outreach.outbox.title')) ?></h1>
        <p class="text-muted mb-0"><?= e(__('outreach.outbox.count', ['count' => $total])) ?></p>
    </div>
</div>

<div class="card-surface p-3 mb-3">
    <form method="get" action="/app/outreach/outbox" class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1" for="status"><?= e(__('outreach.outbox.status')) ?></label>
            <select class="form-select form-select-sm" id="status" name="status">
                <option value=""><?= e(__('admin.waitlist.all')) ?></option>
                <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(__('outreach.status.' . $s)) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1" for="channel"><?= e(__('outreach.channel')) ?></label>
            <select class="form-select form-select-sm" id="channel" name="channel">
                <option value=""><?= e(__('admin.waitlist.all')) ?></option>
                <option value="whatsapp" <?= ($filters['channel'] ?? '') === 'whatsapp' ? 'selected' : '' ?>><?= e(__('outreach.channels.whatsapp')) ?></option>
                <option value="email" <?= ($filters['channel'] ?? '') === 'email' ? 'selected' : '' ?>><?= e(__('outreach.channels.email')) ?></option>
            </select>
        </div>
        <div class="col-6 col-md-2 d-grid">
            <button type="submit" class="btn btn-sm btn-brand"><?= e(__('common.actions.filter')) ?></button>
        </div>
    </form>
</div>

<div class="card-surface">
    <?php if ($messages === []): ?>
        <div class="empty-state">
            <i class="bi bi-inbox empty-state__icon"></i>
            <h2 class="h6"><?= e(__('outreach.outbox.empty_title')) ?></h2>
            <p class="mb-0"><?= e(__('outreach.outbox.empty_text')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= e(__('outreach.channel')) ?></th>
                        <th><?= e(__('outreach.outbox.recipient')) ?></th>
                        <th class="d-none d-md-table-cell"><?= e(__('outreach.outbox.preview')) ?></th>
                        <th><?= e(__('outreach.outbox.status')) ?></th>
                        <th class="text-end"><?= e(__('common.actions.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $m): ?>
                        <tr>
                            <td><i class="bi <?= $m['channel'] === 'email' ? 'bi-envelope' : 'bi-whatsapp' ?> me-1"></i><?= e(__('outreach.channels.' . $m['channel'])) ?></td>
                            <td class="fw-semibold"><?= e((string) ($m['to_address'] ?? '—')) ?></td>
                            <td class="d-none d-md-table-cell text-muted small"><?= e(mb_strimwidth((string) $m['body'], 0, 60, '…')) ?></td>
                            <td><span class="badge-soft-primary px-2"><?= e(__('outreach.status.' . $m['status'])) ?></span></td>
                            <td class="text-end">
                                <?php if ($m['status'] === 'pending_approval' && can('outreach.approve')): ?>
                                    <form method="post" action="/app/outreach/messages/<?= (int) $m['id'] ?>/approve" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> <?= e(__('outreach.outbox.approve')) ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if (!in_array($m['status'], ['sent', 'delivered', 'read', 'cancelled'], true) && can('outreach.cancel')): ?>
                                    <form method="post" action="/app/outreach/messages/<?= (int) $m['id'] ?>/cancel" class="d-inline" data-confirm="<?= e(__('outreach.outbox.confirm_cancel')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
    <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e($qs(['page' => $p])) ?>"><?= $p ?></a></li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>
