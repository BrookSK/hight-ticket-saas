<?php
/**
 * Shareable commercial reports listing.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $reports
 */
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e(__('outreach.reports.title')) ?></h1>
        <p class="text-muted mb-0"><?= e(__('outreach.reports.subtitle')) ?></p>
    </div>
</div>

<div class="card-surface">
    <?php if ($reports === []): ?>
        <div class="empty-state">
            <i class="bi bi-file-earmark-bar-graph empty-state__icon"></i>
            <h2 class="h6"><?= e(__('outreach.reports.empty_title')) ?></h2>
            <p class="mb-0"><?= e(__('outreach.reports.empty_text')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr>
                    <th><?= e(__('outreach.reports.report')) ?></th>
                    <th class="text-center"><?= e(__('outreach.reports.views')) ?></th>
                    <th><?= e(__('outreach.reports.status')) ?></th>
                    <th class="text-end"><?= e(__('common.actions.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($reports as $r): ?>
                        <?php $revoked = $r['revoked_at'] !== null; ?>
                        <tr>
                            <td class="fw-semibold"><?= e((string) ($r['title'] ?? '—')) ?><div class="small text-muted fw-normal"><?= e(__('outreach.kinds.' . ($r['type'] ?? 'diagnosis'))) ?></div></td>
                            <td class="text-center"><?= (int) ($r['views'] ?? 0) ?></td>
                            <td>
                                <?php if ($revoked): ?><span class="badge bg-secondary"><?= e(__('outreach.reports.revoked')) ?></span>
                                <?php else: ?><span class="badge bg-success"><?= e(__('outreach.reports.active')) ?></span><?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="/app/outreach/reports/<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                <?php if (!$revoked && can('outreach.update')): ?>
                                    <form method="post" action="/app/outreach/reports/<?= (int) $r['id'] ?>/revoke" class="d-inline" data-confirm="<?= e(__('outreach.reports.confirm_revoke')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-slash-circle"></i></button>
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
