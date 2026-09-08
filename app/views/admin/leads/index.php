<?php
/**
 * Leads listing with filters (status, temperature, score band, search).
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $leads
 * @var array<string,mixed> $filters
 * @var list<string> $statuses @var list<string> $temperatures
 * @var int $page @var int $pages @var int $total
 */
$qs = static fn (array $extra): string => '?' . http_build_query(array_merge(array_filter($filters, static fn ($v) => $v !== '' && $v !== null), $extra));
$scoreClass = static fn (?int $s): string => $s === null ? 'text-muted' : ($s >= 75 ? 'text-success' : ($s >= 60 ? 'text-warning' : 'text-danger'));
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e(__('crm.leads.title')) ?></h1>
        <p class="text-muted mb-0"><?= e(__('crm.leads.count', ['count' => $total])) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="/app/pipeline" class="btn btn-outline-secondary"><i class="bi bi-kanban me-1"></i><?= e(__('crm.pipeline.title')) ?></a>
        <?php if (can('leads.create')): ?><a href="/app/leads/create" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i><?= e(__('crm.leads.new')) ?></a><?php endif; ?>
    </div>
</div>

<div class="card-surface p-3 mb-3">
    <form method="get" action="/app/leads" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label small mb-1" for="search"><?= e(__('common.actions.search')) ?></label>
            <input type="search" class="form-control form-control-sm" id="search" name="search" value="<?= e((string) ($filters['search'] ?? '')) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="status"><?= e(__('crm.leads.status')) ?></label>
            <select class="form-select form-select-sm" id="status" name="status">
                <option value=""><?= e(__('admin.waitlist.all')) ?></option>
                <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(__('crm.status.' . $s)) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="temperature"><?= e(__('crm.leads.temperature')) ?></label>
            <select class="form-select form-select-sm" id="temperature" name="temperature">
                <option value=""><?= e(__('admin.waitlist.all')) ?></option>
                <?php foreach ($temperatures as $tp): ?><option value="<?= $tp ?>" <?= ($filters['temperature'] ?? '') === $tp ? 'selected' : '' ?>><?= e(__('crm.temperature.' . $tp)) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1" for="score_band"><?= e(__('crm.leads.score_band')) ?></label>
            <select class="form-select form-select-sm" id="score_band" name="score_band">
                <option value=""><?= e(__('admin.waitlist.all')) ?></option>
                <?php foreach (['0-39', '40-59', '60-74', '75-89', '90-100'] as $band): ?>
                    <option value="<?= $band ?>" <?= ($filters['score_band'] ?? '') === $band ? 'selected' : '' ?>><?= $band ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2 d-grid">
            <button type="submit" class="btn btn-sm btn-brand"><?= e(__('common.actions.filter')) ?></button>
        </div>
    </form>
</div>

<div class="card-surface">
    <?php if ($leads === []): ?>
        <div class="empty-state">
            <i class="bi bi-briefcase empty-state__icon"></i>
            <h2 class="h6"><?= e(__('crm.leads.empty_title')) ?></h2>
            <p class="mb-3"><?= e(__('crm.leads.empty_text')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= e(__('crm.companies.name')) ?></th>
                        <th class="d-none d-md-table-cell"><?= e(__('crm.leads.service')) ?></th>
                        <th><?= e(__('crm.leads.status')) ?></th>
                        <th class="d-none d-md-table-cell"><?= e(__('crm.leads.temperature')) ?></th>
                        <th class="text-center"><?= e(__('crm.leads.score')) ?></th>
                        <th class="text-end d-none d-lg-table-cell"><?= e(__('crm.leads.value')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <tr onclick="window.location='/app/leads/<?= (int) $lead['id'] ?>'" style="cursor:pointer;">
                            <td class="fw-semibold"><?= e($lead['company_name']) ?><div class="small text-muted fw-normal"><?= e($lead['title'] ?? '') ?></div></td>
                            <td class="d-none d-md-table-cell text-muted"><?= e($lead['service_type'] ?? '—') ?></td>
                            <td><span class="badge-soft-primary px-2"><?= e(__('crm.status.' . $lead['status'])) ?></span></td>
                            <td class="d-none d-md-table-cell"><?= e(__('crm.temperature.' . $lead['temperature'])) ?></td>
                            <td class="text-center fw-bold <?= $scoreClass($lead['latest_score'] !== null ? (int) $lead['latest_score'] : null) ?>"><?= $lead['latest_score'] !== null ? (int) $lead['latest_score'] : '—' ?></td>
                            <td class="text-end d-none d-lg-table-cell"><?= $lead['estimated_value'] !== null ? e($lead['currency'] . ' ' . number_format((float) $lead['estimated_value'], 2, ',', '.')) : '—' ?></td>
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
