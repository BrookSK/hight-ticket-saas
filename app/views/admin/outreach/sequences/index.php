<?php
/**
 * Follow-up sequences listing.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $sequences
 */
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e(__('outreach.sequences.title')) ?></h1>
        <p class="text-muted mb-0"><?= e(__('outreach.sequences.subtitle')) ?></p>
    </div>
    <?php if (can('outreach.manage_sequences')): ?>
        <a href="/app/outreach/sequences/create" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i><?= e(__('outreach.sequences.new')) ?></a>
    <?php endif; ?>
</div>

<div class="card-surface">
    <?php if ($sequences === []): ?>
        <div class="empty-state">
            <i class="bi bi-diagram-3 empty-state__icon"></i>
            <h2 class="h6"><?= e(__('outreach.sequences.empty_title')) ?></h2>
            <p class="mb-0"><?= e(__('outreach.sequences.empty_text')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr>
                    <th><?= e(__('outreach.sequences.name')) ?></th>
                    <th><?= e(__('outreach.channel')) ?></th>
                    <th><?= e(__('outreach.sequences.active')) ?></th>
                    <th class="text-end"><?= e(__('common.actions.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($sequences as $seq): ?>
                        <tr>
                            <td class="fw-semibold"><?= e((string) $seq['name']) ?></td>
                            <td><?= e(__('outreach.channels.' . $seq['channel'])) ?></td>
                            <td><?= !empty($seq['is_active']) ? '<span class="badge bg-success">' . e(__('common.yes')) . '</span>' : '<span class="badge bg-secondary">' . e(__('common.no')) . '</span>' ?></td>
                            <td class="text-end">
                                <?php if (can('outreach.manage_sequences')): ?>
                                    <a href="/app/outreach/sequences/<?= (int) $seq['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                    <form method="post" action="/app/outreach/sequences/<?= (int) $seq['id'] ?>/delete" class="d-inline" data-confirm="<?= e(__('outreach.sequences.confirm_delete')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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
