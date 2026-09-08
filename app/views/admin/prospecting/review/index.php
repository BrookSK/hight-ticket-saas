<?php
/**
 * Opportunity review listing with filters + batch convert.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $results
 * @var array<string,mixed> $filters
 * @var int $page @var int $pages @var int $total
 */
$oppClass = static fn (?int $s): string => $s === null ? 'text-muted' : ($s >= 70 ? 'text-success' : ($s >= 40 ? 'text-warning' : 'text-muted'));
$qs = static fn (array $extra): string => '?' . http_build_query(array_merge(array_filter($filters, static fn ($v) => $v !== '' && $v !== null), $extra));
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e(__('prospecting.review.title')) ?></h1>
        <p class="text-muted mb-0"><?= e(__('prospecting.review.count', ['count' => $total])) ?></p>
    </div>
</div>

<div class="card-surface p-3 mb-3">
    <form method="get" action="/app/prospecting/review" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label small mb-1" for="search"><?= e(__('common.actions.search')) ?></label>
            <input type="search" class="form-control form-control-sm" id="search" name="search" value="<?= e((string) ($filters['search'] ?? '')) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="status"><?= e(__('prospecting.review.status')) ?></label>
            <select class="form-select form-select-sm" id="status" name="status">
                <?php foreach (['qualified', 'converted', 'discarded', ''] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s === '' ? e(__('admin.waitlist.all')) : e(__('prospecting.result_status.' . $s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="priority"><?= e(__('prospecting.review.priority')) ?></label>
            <select class="form-select form-select-sm" id="priority" name="priority">
                <option value=""><?= e(__('admin.waitlist.all')) ?></option>
                <?php foreach (['high', 'medium', 'low'] as $p): ?>
                    <option value="<?= $p ?>" <?= ($filters['priority'] ?? '') === $p ? 'selected' : '' ?>><?= e(__('prospecting.priority.' . $p)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="website"><?= e(__('prospecting.review.website')) ?></label>
            <select class="form-select form-select-sm" id="website" name="website">
                <option value=""><?= e(__('admin.waitlist.all')) ?></option>
                <option value="with" <?= ($filters['website'] ?? '') === 'with' ? 'selected' : '' ?>><?= e(__('prospecting.review.with_site')) ?></option>
                <option value="without" <?= ($filters['website'] ?? '') === 'without' ? 'selected' : '' ?>><?= e(__('prospecting.review.without_site')) ?></option>
            </select>
        </div>
        <div class="col-6 col-md-3 d-grid">
            <button type="submit" class="btn btn-sm btn-brand"><?= e(__('common.actions.filter')) ?></button>
        </div>
    </form>
</div>

<?php if ($results === []): ?>
    <div class="card-surface"><div class="empty-state">
        <i class="bi bi-stars empty-state__icon"></i>
        <h2 class="h6"><?= e(__('prospecting.review.empty_title')) ?></h2>
        <p class="mb-0"><?= e(__('prospecting.review.empty_text')) ?></p>
    </div></div>
<?php else: ?>
    <form method="post" action="/app/prospecting/review/convert-batch">
        <?= csrf_field() ?>
        <div class="card-surface">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <?php if (can('prospecting.convert')): ?><th style="width:32px;"><input type="checkbox" id="checkAll"></th><?php endif; ?>
                            <th><?= e(__('prospecting.review.company')) ?></th>
                            <th class="text-center"><?= e(__('prospecting.review.opp_score')) ?></th>
                            <th class="text-center d-none d-md-table-cell"><?= e(__('prospecting.review.site_score')) ?></th>
                            <th class="d-none d-lg-table-cell"><?= e(__('prospecting.review.service')) ?></th>
                            <th><?= e(__('prospecting.review.priority')) ?></th>
                            <th class="text-end"><?= e(__('admin.waitlist.actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $r): ?>
                            <tr>
                                <?php if (can('prospecting.convert')): ?>
                                    <td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" class="row-check"></td>
                                <?php endif; ?>
                                <td>
                                    <a href="/app/prospecting/review/<?= (int) $r['id'] ?>" class="fw-semibold text-decoration-none"><?= e($r['name'] ?: $r['raw_name']) ?></a>
                                    <div class="small text-muted"><?= e($r['domain'] ?: __('prospecting.review.no_site')) ?></div>
                                </td>
                                <td class="text-center fw-bold <?= $oppClass($r['opportunity_score'] !== null ? (int) $r['opportunity_score'] : null) ?>"><?= $r['opportunity_score'] !== null ? (int) $r['opportunity_score'] : '—' ?></td>
                                <td class="text-center d-none d-md-table-cell"><?= $r['site_score'] !== null ? (int) $r['site_score'] : '—' ?></td>
                                <td class="d-none d-lg-table-cell text-muted small"><?= $r['recommended_service'] ? e(__('prospecting.service.' . $r['recommended_service'])) : '—' ?></td>
                                <td><?php if ($r['priority']): ?><span class="badge-soft-primary px-2"><?= e(__('prospecting.priority.' . $r['priority'])) ?></span><?php endif; ?></td>
                                <td class="text-end"><a href="/app/prospecting/review/<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (can('prospecting.convert')): ?>
            <div class="mt-3">
                <button type="submit" class="btn btn-brand" data-confirm="<?= e(__('prospecting.review.confirm_batch')) ?>"><i class="bi bi-briefcase me-1"></i><?= e(__('prospecting.review.convert_selected')) ?></button>
            </div>
        <?php endif; ?>
    </form>
<?php endif; ?>

<?php if ($pages > 1): ?>
    <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e($qs(['page' => $p])) ?>"><?= $p ?></a></li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>

<script>
document.getElementById('checkAll')?.addEventListener('change', function (e) {
    document.querySelectorAll('.row-check').forEach(function (cb) { cb.checked = e.target.checked; });
});
</script>
