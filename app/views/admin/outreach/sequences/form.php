<?php
/**
 * Sequence create/edit form with dynamic steps.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $sequence
 * @var array<int,array<string,mixed>> $steps
 * @var array<int,array<string,mixed>> $templates
 * @var list<string> $channels
 * @var array<string,string> $errors
 */
$isEdit = !empty($sequence['id']);
$action = $isEdit ? '/app/outreach/sequences/' . (int) $sequence['id'] : '/app/outreach/sequences';
$errors = $errors ?? [];
$steps = $steps ?: [['delay_days' => 0, 'channel' => 'whatsapp', 'template_id' => null, 'stop_on_reply' => 1]];
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0"><?= e($isEdit ? __('outreach.sequences.edit') : __('outreach.sequences.new')) ?></h1>
    <a href="/app/outreach/sequences" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
</div>

<div class="card-surface p-3">
    <form method="post" action="<?= e($action) ?>">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="name"><?= e(__('outreach.sequences.name')) ?></label>
                <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= e((string) ($sequence['name'] ?? '')) ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="channel"><?= e(__('outreach.channel')) ?></label>
                <select class="form-select" id="channel" name="channel">
                    <?php foreach ($channels as $ch): ?><option value="<?= e($ch) ?>" <?= ($sequence['channel'] ?? '') === $ch ? 'selected' : '' ?>><?= e(__('outreach.channels.' . $ch)) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= !empty($sequence['is_active']) || !$isEdit ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active"><?= e(__('outreach.sequences.active')) ?></label>
                </div>
            </div>
        </div>

        <h2 class="h6"><?= e(__('outreach.sequences.steps')) ?></h2>
        <div id="steps">
            <?php foreach ($steps as $i => $step): ?>
                <div class="row g-2 align-items-end mb-2 step-row">
                    <div class="col-3">
                        <label class="form-label small mb-1"><?= e(__('outreach.sequences.delay_days')) ?></label>
                        <input type="number" min="0" class="form-control form-control-sm" name="steps[<?= $i ?>][delay_days]" value="<?= (int) ($step['delay_days'] ?? 0) ?>">
                    </div>
                    <div class="col-3">
                        <label class="form-label small mb-1"><?= e(__('outreach.channel')) ?></label>
                        <select class="form-select form-select-sm" name="steps[<?= $i ?>][channel]">
                            <?php foreach ($channels as $ch): ?><option value="<?= e($ch) ?>" <?= ($step['channel'] ?? '') === $ch ? 'selected' : '' ?>><?= e(__('outreach.channels.' . $ch)) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label small mb-1"><?= e(__('outreach.prepare.template')) ?></label>
                        <select class="form-select form-select-sm" name="steps[<?= $i ?>][template_id]">
                            <option value=""><?= e(__('outreach.prepare.no_template')) ?></option>
                            <?php foreach ($templates as $tpl): ?><option value="<?= (int) $tpl['id'] ?>" <?= (int) ($step['template_id'] ?? 0) === (int) $tpl['id'] ? 'selected' : '' ?>><?= e((string) $tpl['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-2 d-flex align-items-center gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="steps[<?= $i ?>][stop_on_reply]" value="1" <?= !empty($step['stop_on_reply']) ? 'checked' : '' ?>>
                            <label class="form-check-label small"><?= e(__('outreach.sequences.stop_on_reply')) ?></label>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="addStep"><i class="bi bi-plus-lg me-1"></i><?= e(__('outreach.sequences.add_step')) ?></button>

        <div><button type="submit" class="btn btn-brand"><i class="bi bi-save me-1"></i><?= e(__('common.actions.save')) ?></button></div>
    </form>
</div>

<template id="stepTemplate">
    <div class="row g-2 align-items-end mb-2 step-row">
        <div class="col-3"><label class="form-label small mb-1"><?= e(__('outreach.sequences.delay_days')) ?></label><input type="number" min="0" class="form-control form-control-sm" name="steps[__i__][delay_days]" value="0"></div>
        <div class="col-3"><label class="form-label small mb-1"><?= e(__('outreach.channel')) ?></label>
            <select class="form-select form-select-sm" name="steps[__i__][channel]"><?php foreach ($channels as $ch): ?><option value="<?= e($ch) ?>"><?= e(__('outreach.channels.' . $ch)) ?></option><?php endforeach; ?></select>
        </div>
        <div class="col-4"><label class="form-label small mb-1"><?= e(__('outreach.prepare.template')) ?></label>
            <select class="form-select form-select-sm" name="steps[__i__][template_id]"><option value=""><?= e(__('outreach.prepare.no_template')) ?></option><?php foreach ($templates as $tpl): ?><option value="<?= (int) $tpl['id'] ?>"><?= e((string) $tpl['name']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="col-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="steps[__i__][stop_on_reply]" value="1" checked><label class="form-check-label small"><?= e(__('outreach.sequences.stop_on_reply')) ?></label></div></div>
    </div>
</template>

<script>
(function () {
    let idx = <?= count($steps) ?>;
    document.getElementById('addStep').addEventListener('click', function () {
        const tpl = document.getElementById('stepTemplate').innerHTML.replace(/__i__/g, idx++);
        document.getElementById('steps').insertAdjacentHTML('beforeend', tpl);
    });
})();
</script>
