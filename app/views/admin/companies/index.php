<?php
/**
 * Companies listing.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $companies
 * @var array<string,mixed> $filters
 * @var int $page @var int $pages @var int $total
 */
$qs = static fn (array $extra): string => '?' . http_build_query(array_merge($filters, $extra));
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e(__('crm.companies.title')) ?></h1>
        <p class="text-muted mb-0"><?= e(__('crm.companies.count', ['count' => $total])) ?></p>
    </div>
    <?php if (can('companies.create')): ?>
        <a href="/app/companies/create" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i><?= e(__('crm.companies.new')) ?></a>
    <?php endif; ?>
</div>

<div class="card-surface p-3 mb-3">
    <form method="get" action="/app/companies" class="row g-2 align-items-end">
        <div class="col-12 col-md-6">
            <label class="form-label small mb-1" for="search"><?= e(__('common.actions.search')) ?></label>
            <input type="search" class="form-control form-control-sm" id="search" name="search" value="<?= e((string) ($filters['search'] ?? '')) ?>" placeholder="<?= e(__('crm.companies.search_ph')) ?>">
        </div>
        <div class="col-8 col-md-4">
            <label class="form-label small mb-1" for="status"><?= e(__('crm.companies.status')) ?></label>
            <select class="form-select form-select-sm" id="status" name="status">
                <option value=""><?= e(__('admin.waitlist.all')) ?></option>
                <?php foreach (['active', 'inactive', 'archived'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(__('crm.company_status.' . $s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4 col-md-2 d-grid">
            <button type="submit" class="btn btn-sm btn-brand"><?= e(__('common.actions.filter')) ?></button>
        </div>
    </form>
</div>

<div class="card-surface">
    <?php if ($companies === []): ?>
        <div class="empty-state">
            <i class="bi bi-building empty-state__icon"></i>
            <h2 class="h6"><?= e(__('crm.companies.empty_title')) ?></h2>
            <p class="mb-3"><?= e(__('crm.companies.empty_text')) ?></p>
            <?php if (can('companies.create')): ?><a href="/app/companies/create" class="btn btn-brand"><?= e(__('crm.companies.new')) ?></a><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= e(__('crm.companies.name')) ?></th>
                        <th class="d-none d-md-table-cell"><?= e(__('crm.companies.website')) ?></th>
                        <th class="d-none d-lg-table-cell"><?= e(__('crm.companies.segment')) ?></th>
                        <th><?= e(__('crm.companies.status')) ?></th>
                        <th class="text-end"><?= e(__('admin.waitlist.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $c): ?>
                        <tr>
                            <td><a href="/app/companies/<?= (int) $c['id'] ?>" class="fw-semibold text-decoration-none"><?= e($c['trade_name']) ?></a></td>
                            <td class="d-none d-md-table-cell text-muted"><?= e($c['domain'] ?? '—') ?></td>
                            <td class="d-none d-lg-table-cell text-muted"><?= e($c['segment'] ?? '—') ?></td>
                            <td><span class="badge-soft-primary px-2"><?= e(__('crm.company_status.' . $c['status'])) ?></span></td>
                            <td class="text-end">
                                <a href="/app/companies/<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
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
