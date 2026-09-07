<?php
/**
 * Admin users listing.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $users
 */
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0"><?= e(__('admin.users.title')) ?></h1>
    <?php if (can('users.create')): ?>
        <a href="/app/users/create" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i><?= e(__('admin.users.new')) ?></a>
    <?php endif; ?>
</div>

<div class="card-surface">
    <?php if ($users === []): ?>
        <div class="empty-state"><i class="bi bi-person empty-state__icon"></i><p class="mb-0"><?= e(__('common.empty.message')) ?></p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= e(__('admin.users.name')) ?></th>
                        <th><?= e(__('admin.users.email')) ?></th>
                        <th class="d-none d-md-table-cell"><?= e(__('admin.users.role')) ?></th>
                        <th><?= e(__('admin.users.status')) ?></th>
                        <th class="text-end"><?= e(__('admin.waitlist.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= e($u['name']) ?> <?php if ((int) $u['is_super_admin'] === 1): ?><i class="bi bi-star-fill text-warning ms-1" title="Super Admin"></i><?php endif; ?></td>
                            <td class="text-muted"><?= e($u['email']) ?></td>
                            <td class="d-none d-md-table-cell text-muted"><?= e($u['role_name'] ?? '—') ?></td>
                            <td><span class="badge-soft-primary px-2"><?= e(__('common.status.' . ($u['status'] === 'active' ? 'active' : 'inactive'))) ?></span></td>
                            <td class="text-end">
                                <?php if (can('users.edit')): ?>
                                    <a href="/app/users/<?= (int) $u['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                    <?php if (auth()?->isSuperAdmin() && (int) $u['is_super_admin'] !== 1): ?>
                                        <form method="post" action="/app/users/<?= (int) $u['id'] ?>/impersonate" class="d-inline m-0">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?= e(__('admin.users.impersonate')) ?>"><i class="bi bi-incognito"></i></button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="/app/users/<?= (int) $u['id'] ?>/delete" class="d-inline m-0">
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
