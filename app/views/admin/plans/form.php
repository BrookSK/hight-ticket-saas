<?php
/**
 * Admin plan create/edit form.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed>|null $plan
 * @var array<string,string> $errors
 */
$plan = $plan ?? [];
$errors = $errors ?? [];
$id = isset($plan['id']) ? (int) $plan['id'] : 0;
$action = $id > 0 ? '/app/plans/' . $id : '/app/plans';
$val = static fn (string $k, string $d = ''): string => e((string) ($plan[$k] ?? $d));
$featuresText = is_array($plan['features'] ?? null) ? implode("\n", $plan['features']) : (string) ($plan['features'] ?? '');
$limitsText = is_array($plan['limitations'] ?? null) ? implode("\n", $plan['limitations']) : (string) ($plan['limitations'] ?? '');
?>
<div class="mb-4">
    <a href="/app/plans" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
    <h1 class="h3 mb-0 mt-1"><?= e($id > 0 ? __('admin.plans.edit') : __('admin.plans.new')) ?></h1>
</div>

<div class="card-surface p-4" style="max-width:760px;">
    <form method="post" action="<?= e($action) ?>">
        <?= csrf_field() ?>
        <?php if ($id > 0): ?><?= method_field('PUT') ?><?php endif; ?>

        <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="name"><?= e(__('admin.plans.name')) ?> *</label>
                <input type="text" class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>" id="name" name="name" value="<?= $val('name') ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="slug"><?= e(__('admin.plans.slug')) ?> *</label>
                <input type="text" class="form-control<?= isset($errors['slug']) ? ' is-invalid' : '' ?>" id="slug" name="slug" value="<?= $val('slug') ?>" required>
                <?php if (isset($errors['slug'])): ?><div class="invalid-feedback"><?= e($errors['slug']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="description"><?= e(__('admin.plans.description')) ?></label>
            <input type="text" class="form-control" id="description" name="description" value="<?= $val('description') ?>">
        </div>

        <div class="row">
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="price_monthly"><?= e(__('admin.plans.monthly')) ?></label>
                <input type="text" class="form-control" id="price_monthly" name="price_monthly" value="<?= $val('price_monthly') ?>" placeholder="0,00">
            </div>
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="price_yearly"><?= e(__('admin.plans.yearly')) ?></label>
                <input type="text" class="form-control" id="price_yearly" name="price_yearly" value="<?= $val('price_yearly') ?>" placeholder="0,00">
            </div>
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="currency"><?= e(__('admin.plans.currency')) ?></label>
                <input type="text" class="form-control" id="currency" name="currency" value="<?= $val('currency', 'BRL') ?>">
            </div>
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="sort_order"><?= e(__('admin.plans.order')) ?></label>
                <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?= $val('sort_order', '0') ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="features"><?= e(__('admin.plans.features')) ?></label>
                <textarea class="form-control" id="features" name="features" rows="4" placeholder="<?= e(__('admin.plans.one_per_line')) ?>"><?= e($featuresText) ?></textarea>
            </div>
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="limitations"><?= e(__('admin.plans.limitations')) ?></label>
                <textarea class="form-control" id="limitations" name="limitations" rows="4" placeholder="<?= e(__('admin.plans.one_per_line')) ?>"><?= e($limitsText) ?></textarea>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="cta_label"><?= e(__('admin.plans.cta_label')) ?></label>
                <input type="text" class="form-control" id="cta_label" name="cta_label" value="<?= $val('cta_label') ?>">
            </div>
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="cta_url"><?= e(__('admin.plans.cta_url')) ?></label>
                <input type="text" class="form-control" id="cta_url" name="cta_url" value="<?= $val('cta_url') ?>">
            </div>
        </div>

        <div class="row align-items-center">
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="status"><?= e(__('admin.plans.status')) ?></label>
                <select class="form-select" id="status" name="status">
                    <option value="active" <?= ($plan['status'] ?? 'active') === 'active' ? 'selected' : '' ?>><?= e(__('common.status.active')) ?></option>
                    <option value="inactive" <?= ($plan['status'] ?? '') === 'inactive' ? 'selected' : '' ?>><?= e(__('common.status.inactive')) ?></option>
                </select>
            </div>
            <div class="col-6 col-md-3 mb-3 mt-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" <?= (int) ($plan['is_featured'] ?? 0) === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_featured"><?= e(__('admin.plans.featured')) ?></label>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-2">
            <button type="submit" class="btn btn-brand"><?= e(__('common.actions.save')) ?></button>
            <a href="/app/plans" class="btn btn-outline-secondary"><?= e(__('common.actions.cancel')) ?></a>
        </div>
    </form>
</div>
