<?php
/**
 * Campaigns listing.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $campaigns
 * @var int $page @var int $pages @var int $total
 */
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e(__('prospecting.campaigns.title')) ?></h1>
        <p class="text-muted mb-0"><?= e(__('prospecting.campaigns.count', ['count' => $total])) ?></p>
    </div>
    <?php if (can('prospecting.create')): ?>
        <a href="/app/prospecting/campaigns/create" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i><?= e(__('prospecting.campaigns.new')) ?></a>
    <?php endif; ?>
</div>

<div class="card-surface">
    <?php if ($campaigns === []): ?>
        <div class="empty-state">
            <i class="bi bi-megaphone empty-state__icon"></i>
            <h2 class="h6"><?= e(__('prospecting.campaigns.empty_title')) ?></h2>
            <p class="mb-3"><?= e(__('prospecting.campaigns.empty_text')) ?></p>
            <?php if (can('prospecting.create')): ?><a href="/app/prospecting/campaigns/create" class="btn btn-brand"><?= e(__('prospecting.campaigns.new_first')) ?></a><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= e(__('prospecting.campaigns.name')) ?></th>
                        <th><?= e(__('prospecting.campaigns.status')) ?></th>
                        <th class="text-center d-none d-md-table-cell"><?= e(__('prospecting.campaigns.discovered')) ?></th>
                        <th class="text-center d-none d-md-table-cell"><?= e(__('prospecting.campaigns.audited')) ?></th>
                        <th class="text-end"><?= e(__('admin.waitlist.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $c): ?>
                        <tr>
                            <td><a href="/app/prospecting/campaigns/<?= (int) $c['id'] ?>" class="fw-semibold text-decoration-none"><?= e($c['name']) ?></a></td>
                            <td><span class="badge-soft-primary px-2"><?= e(__('prospecting.status.' . $c['status'])) ?></span></td>
                            <td class="text-center d-none d-md-table-cell"><?= (int) $c['count_discovered'] ?></td>
                            <td class="text-center d-none d-md-table-cell"><?= (int) $c['count_audited'] ?></td>
                            <td class="text-end"><a href="/app/prospecting/campaigns/<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
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
