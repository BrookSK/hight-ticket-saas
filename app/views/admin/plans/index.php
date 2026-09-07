<?php
/**
 * Admin plans listing.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $plans
 */
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0"><?= e(__('admin.plans.title')) ?></h1>
    <?php if (can('plans.create')): ?>
        <a href="/app/plans/create" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i><?= e(__('admin.plans.new')) ?></a>
    <?php endif; ?>
</div>

<div class="card-surface">
    <?php if ($plans === []): ?>
        <div class="empty-state">
            <i class="bi bi-tags empty-state__icon"></i>
            <p class="mb-0"><?= e(__('common.empty.message')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= e(__('admin.plans.name')) ?></th>
                        <th class="d-none d-md-table-cell"><?= e(__('admin.plans.slug')) ?></th>
                        <th><?= e(__('admin.plans.monthly')) ?></th>
                        <th><?= e(__('admin.plans.status')) ?></th>
                        <th class="text-end"><?= e(__('admin.waitlist.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td><?= e($plan['name']) ?> <?php if ((int) $plan['is_featured'] === 1): ?><span class="badge-soft-primary px-2 ms-1"><?= e(__('site.plans.featured')) ?></span><?php endif; ?></td>
                            <td class="d-none d-md-table-cell text-muted"><?= e($plan['slug']) ?></td>
                            <td><?= $plan['price_monthly'] !== null ? e($plan['currency'] . ' ' . number_format((float) $plan['price_monthly'], 2, ',', '.')) : '—' ?></td>
                            <td><span class="badge-soft-primary px-2"><?= e(__('common.status.' . ($plan['status'] === 'active' ? 'active' : 'inactive'))) ?></span></td>
                            <td class="text-end">
                                <?php if (can('plans.edit')): ?>
                                    <a href="/app/plans/<?= (int) $plan['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                    <form method="post" action="/app/plans/<?= (int) $plan['id'] ?>/delete" class="d-inline m-0">
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
