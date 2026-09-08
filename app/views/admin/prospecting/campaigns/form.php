<?php
/**
 * New campaign form (wizard-like, single page with sections).
 * @var App\Libraries\Translator $t
 * @var array<string,string> $errors
 * @var array<string,mixed> $old
 */
$errors = $errors ?? [];
$old = $old ?? [];
$v = static fn (string $k): string => e((string) ($old[$k] ?? ''));
?>
<div class="mb-4">
    <a href="/app/prospecting/campaigns" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
    <h1 class="h3 mb-0 mt-1"><?= e(__('prospecting.campaigns.new')) ?></h1>
    <p class="text-muted mb-0"><?= e(__('prospecting.campaigns.new_hint')) ?></p>
</div>

<div class="card-surface p-4" style="max-width:820px;">
    <form method="post" action="/app/prospecting/campaigns">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label" for="name"><?= e(__('prospecting.campaigns.name')) ?> *</label>
            <input type="text" class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>" id="name" name="name" value="<?= $v('name') ?>" placeholder="<?= e(__('prospecting.campaigns.name_ph')) ?>" required>
            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
        </div>

        <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="segment"><?= e(__('prospecting.campaigns.segment')) ?></label>
                <input type="text" class="form-control" id="segment" name="segment" value="<?= $v('segment') ?>" placeholder="<?= e(__('prospecting.campaigns.segment_ph')) ?>">
            </div>
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="city"><?= e(__('prospecting.campaigns.city')) ?></label>
                <input type="text" class="form-control" id="city" name="city" value="<?= $v('city') ?>">
            </div>
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="state"><?= e(__('prospecting.campaigns.state')) ?></label>
                <input type="text" class="form-control" id="state" name="state" value="<?= $v('state') ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="input_payload"><?= e(__('prospecting.campaigns.list')) ?></label>
            <textarea class="form-control" id="input_payload" name="input_payload" rows="6" placeholder="<?= e(__('prospecting.campaigns.list_ph')) ?>"><?= $v('input_payload') ?></textarea>
            <div class="form-text"><?= e(__('prospecting.campaigns.list_hint')) ?></div>
        </div>

        <div class="row">
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="min_opportunity_score"><?= e(__('prospecting.campaigns.min_score')) ?></label>
                <input type="number" class="form-control" id="min_opportunity_score" name="min_opportunity_score" value="<?= $v('min_opportunity_score') ?: '0' ?>" min="0" max="100">
            </div>
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="max_results"><?= e(__('prospecting.campaigns.max_results')) ?></label>
                <input type="number" class="form-control" id="max_results" name="max_results" value="<?= $v('max_results') ?: '100' ?>" min="1">
            </div>
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="max_audits"><?= e(__('prospecting.campaigns.max_audits')) ?></label>
                <input type="number" class="form-control" id="max_audits" name="max_audits" value="<?= $v('max_audits') ?: '50' ?>" min="0">
            </div>
            <div class="col-6 col-md-3 mb-3 mt-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="auto_audit" name="auto_audit" value="1" checked>
                    <label class="form-check-label" for="auto_audit"><?= e(__('prospecting.campaigns.auto_audit')) ?></label>
                </div>
            </div>
        </div>

        <div class="alert alert-light border small">
            <i class="bi bi-info-circle me-1"></i><?= e(__('prospecting.campaigns.preview_note')) ?>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-brand"><?= e(__('prospecting.campaigns.create_action')) ?></button>
            <a href="/app/prospecting/campaigns" class="btn btn-outline-secondary"><?= e(__('common.actions.cancel')) ?></a>
        </div>
    </form>
</div>
