<?php
/**
 * Outreach template create/edit form.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $template
 * @var list<string> $channels @var list<string> $kinds
 * @var array<int,string> $variables
 * @var array<string,string> $errors
 * @var array<int,string> $invalid
 */
$isEdit = !empty($template['id']);
$action = $isEdit ? '/app/outreach/templates/' . (int) $template['id'] : '/app/outreach/templates';
$errors = $errors ?? [];
$invalid = $invalid ?? [];
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0"><?= e($isEdit ? __('outreach.templates.edit') : __('outreach.templates.new')) ?></h1>
    <a href="/app/outreach/templates" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
</div>

<?php if ($invalid !== []): ?>
    <div class="alert alert-danger"><?= e(__('outreach.errors.unknown_variable')) ?>: <?php foreach ($invalid as $iv): ?><code>{{<?= e($iv) ?>}}</code> <?php endforeach; ?></div>
<?php endif; ?>

<div class="card-surface p-3">
    <form method="post" action="<?= e($action) ?>">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="name"><?= e(__('outreach.templates.name')) ?></label>
                <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= e((string) ($template['name'] ?? '')) ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="channel"><?= e(__('outreach.channel')) ?></label>
                <select class="form-select" id="channel" name="channel">
                    <?php foreach ($channels as $ch): ?><option value="<?= e($ch) ?>" <?= ($template['channel'] ?? '') === $ch ? 'selected' : '' ?>><?= e(__('outreach.channels.' . $ch)) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="kind"><?= e(__('outreach.templates.kind')) ?></label>
                <select class="form-select" id="kind" name="kind">
                    <?php foreach ($kinds as $k): ?><option value="<?= e($k) ?>" <?= ($template['kind'] ?? '') === $k ? 'selected' : '' ?>><?= e(__('outreach.kinds.' . $k)) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="subject"><?= e(__('outreach.templates.subject')) ?> <span class="text-muted small">(<?= e(__('outreach.channels.email')) ?>)</span></label>
                <input type="text" class="form-control" id="subject" name="subject" value="<?= e((string) ($template['subject'] ?? '')) ?>">
            </div>
            <div class="col-12">
                <label class="form-label" for="body"><?= e(__('outreach.prepare.message')) ?></label>
                <textarea class="form-control <?= isset($errors['body']) ? 'is-invalid' : '' ?>" id="body" name="body" rows="6" required><?= e((string) ($template['body'] ?? '')) ?></textarea>
                <div class="form-text"><?= e(__('outreach.prepare.variables_hint')) ?>: <?php foreach ($variables as $v): ?><code>{{<?= e($v) ?>}}</code> <?php endforeach; ?></div>
                <?php if (isset($errors['body'])): ?><div class="invalid-feedback d-block"><?= e($errors['body']) ?></div><?php endif; ?>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= !empty($template['is_active']) || !$isEdit ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active"><?= e(__('outreach.templates.active')) ?></label>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-brand"><i class="bi bi-save me-1"></i><?= e(__('common.actions.save')) ?></button>
        </div>
    </form>
</div>
