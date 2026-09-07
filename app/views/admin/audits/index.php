<?php
/**
 * Audit history listing.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $audits
 * @var int $page
 * @var int $pages
 * @var int $total
 */
$scoreClass = static function (?int $s): string {
    if ($s === null) { return 'text-muted'; }
    return $s >= 75 ? 'text-success' : ($s >= 60 ? 'text-warning' : 'text-danger');
};
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e(__('audit.title')) ?></h1>
        <p class="text-muted mb-0"><?= e(__('audit.count', ['count' => $total])) ?></p>
    </div>
    <?php if (can('audits.create')): ?>
        <a href="/app/audits/create" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i><?= e(__('audit.new')) ?></a>
    <?php endif; ?>
</div>

<div class="card-surface">
    <?php if ($audits === []): ?>
        <div class="empty-state">
            <i class="bi bi-radar empty-state__icon" aria-hidden="true"></i>
            <h2 class="h6"><?= e(__('audit.empty_title')) ?></h2>
            <p class="mb-3"><?= e(__('audit.empty_text')) ?></p>
            <?php if (can('audits.create')): ?>
                <a href="/app/audits/create" class="btn btn-brand"><?= e(__('audit.new')) ?></a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= e(__('audit.field.url')) ?></th>
                        <th><?= e(__('audit.field.status')) ?></th>
                        <th class="text-center"><?= e(__('audit.field.score')) ?></th>
                        <th class="d-none d-md-table-cell"><?= e(__('audit.field.date')) ?></th>
                        <th class="text-end"><?= e(__('admin.waitlist.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audits as $a): ?>
                        <tr>
                            <td class="text-truncate" style="max-width:280px;"><?= e($a['host']) ?></td>
                            <td><span class="badge-soft-primary px-2"><?= e(__('audit.status.' . $a['status'])) ?></span></td>
                            <td class="text-center fw-bold <?= $scoreClass($a['score_overall'] !== null ? (int) $a['score_overall'] : null) ?>">
                                <?= $a['score_overall'] !== null ? (int) $a['score_overall'] : '—' ?>
                            </td>
                            <td class="d-none d-md-table-cell text-muted small"><?= e($a['created_at']) ?></td>
                            <td class="text-end">
                                <a href="/app/audits/<?= (int) $a['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                <?php if (can('audits.delete')): ?>
                                    <form method="post" action="/app/audits/<?= (int) $a['id'] ?>/delete" class="d-inline m-0">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="<?= e(__('common.confirm.delete_message')) ?>"><i class="bi bi-trash"></i></button>
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
            <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a></li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>
