<?php
/**
 * Admin roles listing.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $roles
 */
?>
<h1 class="h3 mb-4"><?= e(__('admin.roles.title')) ?></h1>

<div class="card-surface">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th><?= e(__('admin.roles.name')) ?></th>
                    <th class="d-none d-md-table-cell"><?= e(__('admin.roles.description')) ?></th>
                    <th class="text-end"><?= e(__('admin.waitlist.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                    <tr>
                        <td><?= e($role['name']) ?></td>
                        <td class="d-none d-md-table-cell text-muted"><?= e($role['description'] ?? '—') ?></td>
                        <td class="text-end">
                            <?php if (can('roles.edit')): ?>
                                <a href="/app/roles/<?= (int) $role['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-sliders me-1"></i><?= e(__('admin.roles.permissions')) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
