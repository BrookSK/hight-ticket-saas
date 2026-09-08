<?php
/**
 * Company create/edit form.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed>|null $company
 * @var array<string,string> $errors
 */
$company = $company ?? [];
$errors = $errors ?? [];
$id = isset($company['id']) ? (int) $company['id'] : 0;
$action = $id > 0 ? '/app/companies/' . $id : '/app/companies';
$v = static fn (string $k): string => e((string) ($company[$k] ?? ''));
?>
<div class="mb-4">
    <a href="/app/companies" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
    <h1 class="h3 mb-0 mt-1"><?= e($id > 0 ? __('crm.companies.edit') : __('crm.companies.new')) ?></h1>
</div>

<div class="card-surface p-4" style="max-width:820px;">
    <form method="post" action="<?= e($action) ?>">
        <?= csrf_field() ?>
        <?php if ($id > 0): ?><?= method_field('PUT') ?><?php endif; ?>
        <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="trade_name"><?= e(__('crm.companies.trade_name')) ?> *</label>
                <input type="text" class="form-control<?= isset($errors['trade_name']) ? ' is-invalid' : '' ?>" id="trade_name" name="trade_name" value="<?= $v('trade_name') ?>" required>
                <?php if (isset($errors['trade_name'])): ?><div class="invalid-feedback"><?= e($errors['trade_name']) ?></div><?php endif; ?>
            </div>
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="legal_name"><?= e(__('crm.companies.legal_name')) ?></label>
                <input type="text" class="form-control" id="legal_name" name="legal_name" value="<?= $v('legal_name') ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="website"><?= e(__('crm.companies.website')) ?></label>
                <input type="text" class="form-control" id="website" name="website" value="<?= $v('website') ?>" placeholder="https://empresa.com.br">
            </div>
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="cnpj">CNPJ</label>
                <input type="text" class="form-control" id="cnpj" name="cnpj" value="<?= $v('cnpj') ?>">
            </div>
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="segment"><?= e(__('crm.companies.segment')) ?></label>
                <input type="text" class="form-control" id="segment" name="segment" value="<?= $v('segment') ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-4 mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= $v('email') ?>">
            </div>
            <div class="col-6 col-md-4 mb-3">
                <label class="form-label" for="phone"><?= e(__('crm.companies.phone')) ?></label>
                <input type="text" class="form-control" id="phone" name="phone" value="<?= $v('phone') ?>">
            </div>
            <div class="col-6 col-md-4 mb-3">
                <label class="form-label" for="whatsapp">WhatsApp</label>
                <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="<?= $v('whatsapp') ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-5 mb-3">
                <label class="form-label" for="city"><?= e(__('crm.companies.city')) ?></label>
                <input type="text" class="form-control" id="city" name="city" value="<?= $v('city') ?>">
            </div>
            <div class="col-6 col-md-4 mb-3">
                <label class="form-label" for="state"><?= e(__('crm.companies.state')) ?></label>
                <input type="text" class="form-control" id="state" name="state" value="<?= $v('state') ?>">
            </div>
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="zip_code"><?= e(__('crm.companies.zip')) ?></label>
                <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?= $v('zip_code') ?>">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="description"><?= e(__('crm.companies.description')) ?></label>
            <textarea class="form-control" id="description" name="description" rows="2"><?= $v('description') ?></textarea>
        </div>
        <?php if ($id > 0): ?>
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="status"><?= e(__('crm.companies.status')) ?></label>
                <select class="form-select" id="status" name="status">
                    <?php foreach (['active', 'inactive', 'archived'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($company['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= e(__('crm.company_status.' . $s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-brand"><?= e(__('common.actions.save')) ?></button>
            <a href="/app/companies" class="btn btn-outline-secondary"><?= e(__('common.actions.cancel')) ?></a>
        </div>
    </form>
</div>
