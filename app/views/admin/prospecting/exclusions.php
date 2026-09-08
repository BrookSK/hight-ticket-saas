<?php
/**
 * Exclusion (do-not-prospect) list management.
 * @var App\Libraries\Translator $t
 * @var array<int,array<string,mixed>> $items
 */
?>
<div class="mb-4">
    <a href="/app/prospecting" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
    <h1 class="h3 mb-0 mt-1"><?= e(__('prospecting.exclusion.title')) ?></h1>
    <p class="text-muted mb-0"><?= e(__('prospecting.exclusion.hint')) ?></p>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-5">
        <div class="card-surface p-4">
            <h2 class="h6 mb-3"><?= e(__('prospecting.exclusion.add')) ?></h2>
            <form method="post" action="/app/prospecting/exclusions">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label small" for="type"><?= e(__('prospecting.exclusion.type')) ?></label>
                    <select class="form-select form-select-sm" id="type" name="type">
                        <?php foreach (['domain', 'email', 'phone', 'cnpj', 'company'] as $ty): ?>
                            <option value="<?= $ty ?>"><?= e(__('prospecting.exclusion.type_' . $ty)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small" for="value"><?= e(__('prospecting.exclusion.value')) ?></label>
                    <input type="text" class="form-control form-control-sm" id="value" name="value" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small" for="reason"><?= e(__('prospecting.exclusion.reason')) ?></label>
                    <input type="text" class="form-control form-control-sm" id="reason" name="reason">
                </div>
                <button type="submit" class="btn btn-sm btn-brand"><?= e(__('common.actions.save')) ?></button>
            </form>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card-surface">
            <?php if ($items === []): ?>
                <div class="empty-state"><i class="bi bi-slash-circle empty-state__icon"></i><p class="mb-0"><?= e(__('prospecting.exclusion.empty')) ?></p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr>
                            <th><?= e(__('prospecting.exclusion.type')) ?></th>
                            <th><?= e(__('prospecting.exclusion.value')) ?></th>
                            <th class="text-end"><?= e(__('admin.waitlist.actions')) ?></th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= e(__('prospecting.exclusion.type_' . $item['type'])) ?></td>
                                    <td class="text-muted"><?= e($item['value']) ?></td>
                                    <td class="text-end">
                                        <form method="post" action="/app/prospecting/exclusions/<?= (int) $item['id'] ?>/delete" class="m-0"><?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="<?= e(__('common.confirm.delete_message')) ?>"><i class="bi bi-trash"></i></button>
                                        </form>
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
