<?php
/**
 * Opt-out / suppression list management.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $suppressions
 */
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e(__('outreach.suppressions.title')) ?></h1>
        <p class="text-muted mb-0"><?= e(__('outreach.suppressions.subtitle')) ?></p>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card-surface p-3">
            <h2 class="h6 mb-3"><?= e(__('outreach.suppressions.add')) ?></h2>
            <?php if (can('outreach.update')): ?>
            <form method="post" action="/app/outreach/suppressions">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label small mb-1" for="channel"><?= e(__('outreach.channel')) ?></label>
                    <select class="form-select form-select-sm" id="channel" name="channel">
                        <option value="whatsapp"><?= e(__('outreach.channels.whatsapp')) ?></option>
                        <option value="email"><?= e(__('outreach.channels.email')) ?></option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small mb-1" for="value"><?= e(__('outreach.suppressions.value')) ?></label>
                    <input type="text" class="form-control form-control-sm" id="value" name="value" required>
                </div>
                <button type="submit" class="btn btn-sm btn-brand w-100"><?= e(__('outreach.suppressions.add')) ?></button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card-surface">
            <?php if ($suppressions === []): ?>
                <div class="empty-state">
                    <i class="bi bi-slash-circle empty-state__icon"></i>
                    <h2 class="h6"><?= e(__('outreach.suppressions.empty_title')) ?></h2>
                    <p class="mb-0"><?= e(__('outreach.suppressions.empty_text')) ?></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr>
                            <th><?= e(__('outreach.suppressions.type')) ?></th>
                            <th><?= e(__('outreach.suppressions.value')) ?></th>
                            <th><?= e(__('outreach.suppressions.reason')) ?></th>
                            <th class="text-end"><?= e(__('common.actions.actions')) ?></th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($suppressions as $s): ?>
                                <tr>
                                    <td><?= e((string) $s['type']) ?></td>
                                    <td class="fw-semibold"><?= e((string) $s['value']) ?></td>
                                    <td><?= e(__('outreach.suppressions.reasons.' . ($s['reason'] ?? 'manual'))) ?></td>
                                    <td class="text-end">
                                        <?php if (can('outreach.update')): ?>
                                            <form method="post" action="/app/outreach/suppressions/<?= (int) $s['id'] ?>/delete" class="d-inline" data-confirm="<?= e(__('outreach.suppressions.confirm_remove')) ?>">
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
    </div>
</div>
