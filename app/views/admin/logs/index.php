<?php
/**
 * Admin activity logs viewer.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $logs
 */
?>
<h1 class="h3 mb-4"><?= e(__('admin.logs.title')) ?></h1>

<div class="card-surface">
    <?php if ($logs === []): ?>
        <div class="empty-state"><i class="bi bi-clock-history empty-state__icon"></i><p class="mb-0"><?= e(__('common.empty.message')) ?></p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= e(__('admin.logs.date')) ?></th>
                        <th><?= e(__('admin.logs.user')) ?></th>
                        <th><?= e(__('admin.logs.action')) ?></th>
                        <th class="d-none d-md-table-cell"><?= e(__('admin.logs.object')) ?></th>
                        <th class="d-none d-lg-table-cell"><?= e(__('admin.logs.ip')) ?></th>
                        <th class="d-none d-lg-table-cell"><?= e(__('admin.logs.browser')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-muted small"><?= e($log['created_at']) ?></td>
                            <td><?= e($log['user_name'] ?? '—') ?></td>
                            <td><code class="small"><?= e($log['action']) ?></code></td>
                            <td class="d-none d-md-table-cell text-muted small"><?= e(trim(($log['object_type'] ?? '') . ' ' . ($log['object_id'] ?? ''))) ?: '—' ?></td>
                            <td class="d-none d-lg-table-cell text-muted small"><?= e($log['ip'] ?? '—') ?></td>
                            <td class="d-none d-lg-table-cell text-muted small"><?= e(trim(($log['browser'] ?? '') . ' / ' . ($log['os'] ?? ''), ' /')) ?: '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
