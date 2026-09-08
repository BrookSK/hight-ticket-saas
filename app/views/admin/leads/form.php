<?php
/**
 * Lead create/edit form.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $lead
 * @var array<int,array<string,mixed>> $companies (only on create)
 * @var array<string,string> $errors
 */
$lead = $lead ?? [];
$errors = $errors ?? [];
$id = isset($lead['id']) ? (int) $lead['id'] : 0;
$action = $id > 0 ? '/app/leads/' . $id : '/app/leads';
$v = static fn (string $k): string => e((string) ($lead[$k] ?? ''));
$temps = ['cold', 'warm', 'hot'];
$quals = ['unqualified', 'qualifying', 'qualified'];
?>
<div class="mb-4">
    <a href="/app/leads" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
    <h1 class="h3 mb-0 mt-1"><?= e($id > 0 ? __('crm.leads.edit') : __('crm.leads.new')) ?></h1>
</div>

<div class="card-surface p-4" style="max-width:820px;">
    <form method="post" action="<?= e($action) ?>">
        <?= csrf_field() ?>
        <?php if ($id > 0): ?><?= method_field('PUT') ?><?php endif; ?>

        <?php if ($id === 0): ?>
            <div class="mb-3">
                <label class="form-label" for="company_id"><?= e(__('crm.companies.title')) ?> *</label>
                <select class="form-select<?= isset($errors['company_id']) ? ' is-invalid' : '' ?>" id="company_id" name="company_id" required>
                    <option value=""><?= e(__('crm.leads.select_company')) ?></option>
                    <?php foreach ($companies as $co): ?>
                        <option value="<?= (int) $co['id'] ?>" <?= (int) ($lead['company_id'] ?? 0) === (int) $co['id'] ? 'selected' : '' ?>><?= e($co['trade_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['company_id'])): ?><div class="invalid-feedback"><?= e($errors['company_id']) ?></div><?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="title"><?= e(__('crm.leads.lead_title')) ?></label>
                <input type="text" class="form-control" id="title" name="title" value="<?= $v('title') ?>">
            </div>
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="service_type"><?= e(__('crm.leads.service')) ?></label>
                <input type="text" class="form-control" id="service_type" name="service_type" value="<?= $v('service_type') ?>" placeholder="<?= e(__('crm.leads.service_ph')) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="temperature"><?= e(__('crm.leads.temperature')) ?></label>
                <select class="form-select" id="temperature" name="temperature">
                    <?php foreach ($temps as $tp): ?><option value="<?= $tp ?>" <?= ($lead['temperature'] ?? 'cold') === $tp ? 'selected' : '' ?>><?= e(__('crm.temperature.' . $tp)) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="qualification"><?= e(__('crm.leads.qualification')) ?></label>
                <select class="form-select" id="qualification" name="qualification">
                    <?php foreach ($quals as $q): ?><option value="<?= $q ?>" <?= ($lead['qualification'] ?? 'unqualified') === $q ? 'selected' : '' ?>><?= e(__('crm.qualification.' . $q)) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="estimated_value"><?= e(__('crm.leads.value')) ?></label>
                <input type="text" class="form-control" id="estimated_value" name="estimated_value" value="<?= $v('estimated_value') ?>" placeholder="0,00">
            </div>
            <div class="col-6 col-md-3 mb-3">
                <label class="form-label" for="probability"><?= e(__('crm.leads.probability')) ?> (%)</label>
                <input type="number" class="form-control" id="probability" name="probability" value="<?= $v('probability') ?>" min="0" max="100">
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="expected_close_date"><?= e(__('crm.leads.expected_close')) ?></label>
                <input type="date" class="form-control" id="expected_close_date" name="expected_close_date" value="<?= $v('expected_close_date') ?>">
            </div>
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="next_step"><?= e(__('crm.leads.next_step')) ?></label>
                <input type="text" class="form-control" id="next_step" name="next_step" value="<?= $v('next_step') ?>">
            </div>
        </div>

        <?php if ($id > 0): ?>
            <details class="mb-3">
                <summary class="text-muted small mb-2" style="cursor:pointer;"><?= e(__('crm.leads.qualification_detail')) ?></summary>
                <div class="row mt-2">
                    <div class="col-12 mb-2"><label class="form-label small" for="need"><?= e(__('crm.leads.need')) ?></label><textarea class="form-control form-control-sm" id="need" name="need" rows="2"><?= $v('need') ?></textarea></div>
                    <div class="col-12 mb-2"><label class="form-label small" for="problem"><?= e(__('crm.leads.problem')) ?></label><textarea class="form-control form-control-sm" id="problem" name="problem" rows="2"><?= $v('problem') ?></textarea></div>
                    <div class="col-6 mb-2"><label class="form-label small" for="budget"><?= e(__('crm.leads.budget')) ?></label><input type="text" class="form-control form-control-sm" id="budget" name="budget" value="<?= $v('budget') ?>"></div>
                    <div class="col-6 mb-2"><label class="form-label small" for="urgency"><?= e(__('crm.leads.urgency')) ?></label><input type="text" class="form-control form-control-sm" id="urgency" name="urgency" value="<?= $v('urgency') ?>"></div>
                    <div class="col-6 mb-2"><label class="form-label small" for="competitor"><?= e(__('crm.leads.competitor')) ?></label><input type="text" class="form-control form-control-sm" id="competitor" name="competitor" value="<?= $v('competitor') ?>"></div>
                    <div class="col-6 mb-2"><label class="form-label small" for="current_solution"><?= e(__('crm.leads.current_solution')) ?></label><input type="text" class="form-control form-control-sm" id="current_solution" name="current_solution" value="<?= $v('current_solution') ?>"></div>
                </div>
            </details>
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label" for="notes"><?= e(__('crm.leads.notes')) ?></label>
            <textarea class="form-control" id="notes" name="notes" rows="2"><?= $v('notes') ?></textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-brand"><?= e(__('common.actions.save')) ?></button>
            <a href="<?= $id > 0 ? '/app/leads/' . $id : '/app/leads' ?>" class="btn btn-outline-secondary"><?= e(__('common.actions.cancel')) ?></a>
        </div>
    </form>
</div>
