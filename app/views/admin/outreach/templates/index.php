<?php
/**
 * Outreach templates listing.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $templates
 */
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div><h1 class="h3 mb-1"><?= e(__('outreach.templates.title')) ?></h1></div>
    <?php if (can('outreach.manage_templates')): ?>
        <a href="/app/outreach/templates/create" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i><?= e(__('outreach.templates.new')) ?></a>
    <?php endif; ?>
</div>

<div class="card-surface">
    <?php if ($templates === []): ?>
        <div class="empty-state">
            <i class="bi bi-file-text empty-state__icon"></i>
            <h2 class="h6"><?= e(__('outreach.templates.empty_title')) ?></h2>
            <p class="mb-0"><?= e(__('outreach.templates.empty_text')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr>
                    <th><?= e(__('outreach.templates.name')) ?></th>
                    <th><?= e(__('outreach.channel')) ?></th>
                    <th><?= e(__('outreach.templates.kind')) ?></th>
                    <th class="text-end"><?= e(__('common.actions.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($templates as $tpl): ?>
                        <tr>
                            <td class="fw-semibold"><?= e((string) $tpl['name']) ?></td>
                            <td><?= e(__('outreach.channels.' . $tpl['channel'])) ?></td>
                            <td><?= e(__('outreach.kinds.' . $tpl['kind'])) ?></td>
                            <td class="text-end">
                                <?php if (can('outreach.manage_templates')): ?>
                                    <a href="/app/outreach/templates/<?= (int) $tpl['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                    <form method="post" action="/app/outreach/templates/<?= (int) $tpl['id'] ?>/delete" class="d-inline" data-confirm="<?= e(__('outreach.templates.confirm_delete')) ?>">
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
