<?php
/**
 * Admin waitlist listing: filters, table, sorting, pagination, export.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $leads
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var array<string,mixed> $filters
 * @var list<string> $statuses
 */
$qs = static function (array $extra) use ($filters, $orderBy, $direction): string {
    return '?' . http_build_query(array_merge($filters, ['order_by' => $orderBy, 'direction' => $direction], $extra));
};
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e(__('admin.waitlist.title')) ?></h1>
        <p class="text-muted mb-0"><?= e(__('admin.waitlist.count', ['count' => $total])) ?></p>
    </div>
    <?php if (can('waitlist.export')): ?>
        <a href="/app/waitlist/export<?= e($qs([])) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-download me-1"></i><?= e(__('common.actions.export')) ?>
        </a>
    <?php endif; ?>
</div>

<div class="card-surface p-3 mb-3">
    <form method="get" action="/app/waitlist" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label small mb-1" for="search"><?= e(__('common.actions.search')) ?></label>
            <input type="search" class="form-control form-control-sm" id="search" name="search"
                   value="<?= e($filters['search'] ?? '') ?>" placeholder="<?= e(__('admin.waitlist.search_ph')) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="status"><?= e(__('admin.waitlist.status')) ?></label>
            <select class="form-select form-select-sm" id="status" name="status">
                <option value=""><?= e(__('admin.waitlist.all')) ?></option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>>
                        <?= e(__('admin.status.' . $s)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="date_from"><?= e(__('admin.waitlist.from')) ?></label>
            <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="date_to"><?= e(__('admin.waitlist.to')) ?></label>
            <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>">
        </div>
        <div class="col-6 col-md-2 d-grid">
            <button type="submit" class="btn btn-sm btn-brand"><?= e(__('common.actions.filter')) ?></button>
        </div>
    </form>
</div>

<div class="card-surface">
    <?php if ($leads === []): ?>
        <div class="empty-state">
            <i class="bi bi-people empty-state__icon" aria-hidden="true"></i>
            <h2 class="h6"><?= e(__('admin.waitlist.empty_title')) ?></h2>
            <p class="mb-0"><?= e(__('admin.waitlist.empty_text')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><a class="text-decoration-none" href="<?= e($qs(['order_by' => 'name', 'direction' => $direction === 'asc' ? 'desc' : 'asc'])) ?>"><?= e(__('site.waitlist.field_name')) ?></a></th>
                        <th><?= e(__('site.waitlist.field_email')) ?></th>
                        <th class="d-none d-md-table-cell"><?= e(__('site.waitlist.field_phone')) ?></th>
                        <th><?= e(__('admin.waitlist.status')) ?></th>
                        <th class="d-none d-lg-table-cell"><a class="text-decoration-none" href="<?= e($qs(['order_by' => 'created_at', 'direction' => $direction === 'asc' ? 'desc' : 'asc'])) ?>"><?= e(__('admin.waitlist.created')) ?></a></th>
                        <th class="text-end"><?= e(__('admin.waitlist.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><?= e($lead['name']) ?></td>
                            <td class="text-muted"><?= e($lead['email']) ?></td>
                            <td class="d-none d-md-table-cell text-muted"><?= e($lead['phone']) ?></td>
                            <td><span class="badge-soft-primary px-2"><?= e(__('admin.status.' . $lead['status'])) ?></span></td>
                            <td class="d-none d-lg-table-cell text-muted small"><?= e($lead['created_at']) ?></td>
                            <td class="text-end">
                                <a href="/app/waitlist/<?= (int) $lead['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
    <nav class="mt-3" aria-label="<?= e(__('admin.waitlist.pagination')) ?>">
        <ul class="pagination pagination-sm justify-content-center">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e($qs(['page' => $p])) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
