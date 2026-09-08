<?php
/**
 * Company detail: summary, data, contacts, leads, activities/timeline.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $company
 * @var array<int,array<string,mixed>> $contacts
 * @var array<int,array<string,mixed>> $leads
 * @var array<int,array<string,mixed>> $activities
 */
$id = (int) $company['id'];
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <a href="/app/companies" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
        <h1 class="h4 mb-0 mt-1"><?= e($company['trade_name']) ?></h1>
        <?php if (!empty($company['website'])): ?><a href="<?= e($company['website']) ?>" target="_blank" rel="noopener" class="small text-muted"><?= e($company['domain'] ?? $company['website']) ?> <i class="bi bi-box-arrow-up-right"></i></a><?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <?php if (can('leads.create')): ?>
            <a href="/app/leads/create?company_id=<?= $id ?>" class="btn btn-brand"><i class="bi bi-briefcase me-1"></i><?= e(__('crm.leads.new')) ?></a>
        <?php endif; ?>
        <?php if (can('companies.update')): ?>
            <a href="/app/companies/<?= $id ?>/edit" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <!-- Data -->
        <div class="card-surface p-4 mb-3">
            <h2 class="h6 mb-3"><?= e(__('crm.companies.data')) ?></h2>
            <dl class="row mb-0 small">
                <dt class="col-4 text-muted">E-mail</dt><dd class="col-8"><?= e($company['email'] ?: '—') ?></dd>
                <dt class="col-4 text-muted"><?= e(__('crm.companies.phone')) ?></dt><dd class="col-8"><?= e($company['phone'] ?: '—') ?></dd>
                <dt class="col-4 text-muted">CNPJ</dt><dd class="col-8"><?= e($company['cnpj'] ?: '—') ?></dd>
                <dt class="col-4 text-muted"><?= e(__('crm.companies.segment')) ?></dt><dd class="col-8"><?= e($company['segment'] ?: '—') ?></dd>
                <dt class="col-4 text-muted"><?= e(__('crm.companies.city')) ?></dt><dd class="col-8"><?= e(trim(($company['city'] ?? '') . ' / ' . ($company['state'] ?? ''), ' /') ?: '—') ?></dd>
            </dl>
        </div>

        <!-- Contacts -->
        <div class="card-surface p-4 mb-3">
            <h2 class="h6 mb-3"><?= e(__('crm.contacts.title')) ?></h2>
            <?php if ($contacts === []): ?>
                <p class="text-muted small"><?= e(__('crm.contacts.empty')) ?></p>
            <?php else: ?>
                <?php foreach ($contacts as $contact): ?>
                    <div class="d-flex justify-content-between border-bottom py-2 small">
                        <div>
                            <span class="fw-semibold"><?= e(trim($contact['first_name'] . ' ' . ($contact['last_name'] ?? ''))) ?></span>
                            <?php if ((int) $contact['is_primary'] === 1): ?><span class="badge-soft-primary px-2 ms-1"><?= e(__('crm.contacts.primary')) ?></span><?php endif; ?>
                            <div class="text-muted"><?= e($contact['role_title'] ?? '') ?> <?= $contact['email'] ? '· ' . e($contact['email']) : '' ?> <?= $contact['phone'] ? '· ' . e($contact['phone']) : '' ?></div>
                        </div>
                        <?php if (can('contacts.delete')): ?>
                            <form method="post" action="/app/contacts/<?= (int) $contact['id'] ?>/delete" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="<?= e(__('common.confirm.delete_message')) ?>"><i class="bi bi-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (can('contacts.create')): ?>
                <details class="mt-3">
                    <summary class="small text-muted" style="cursor:pointer;"><?= e(__('crm.contacts.add')) ?></summary>
                    <form method="post" action="/app/companies/<?= $id ?>/contacts" class="row g-2 mt-2">
                        <?= csrf_field() ?>
                        <div class="col-6"><input type="text" class="form-control form-control-sm" name="first_name" placeholder="<?= e(__('crm.contacts.first_name')) ?>" required></div>
                        <div class="col-6"><input type="text" class="form-control form-control-sm" name="last_name" placeholder="<?= e(__('crm.contacts.last_name')) ?>"></div>
                        <div class="col-6"><input type="text" class="form-control form-control-sm" name="role_title" placeholder="<?= e(__('crm.contacts.role')) ?>"></div>
                        <div class="col-6"><input type="email" class="form-control form-control-sm" name="email" placeholder="E-mail"></div>
                        <div class="col-6"><input type="text" class="form-control form-control-sm" name="phone" placeholder="<?= e(__('crm.companies.phone')) ?>"></div>
                        <div class="col-6 d-flex align-items-center gap-2">
                            <div class="form-check m-0"><input class="form-check-input" type="checkbox" name="is_primary" value="1" id="cp"><label class="form-check-label small" for="cp"><?= e(__('crm.contacts.primary')) ?></label></div>
                        </div>
                        <div class="col-12"><button type="submit" class="btn btn-sm btn-brand"><?= e(__('common.actions.save')) ?></button></div>
                    </form>
                </details>
            <?php endif; ?>
        </div>

        <!-- Leads -->
        <div class="card-surface p-4">
            <h2 class="h6 mb-3"><?= e(__('crm.leads.title')) ?></h2>
            <?php if ($leads === []): ?>
                <p class="text-muted small mb-0"><?= e(__('crm.leads.empty_text')) ?></p>
            <?php else: ?>
                <?php foreach ($leads as $lead): ?>
                    <div class="d-flex justify-content-between border-bottom py-2 small">
                        <a href="/app/leads/<?= (int) $lead['id'] ?>" class="text-decoration-none"><?= e($lead['title'] ?: __('crm.leads.untitled')) ?></a>
                        <span class="badge-soft-primary px-2"><?= e(__('crm.status.' . $lead['status'])) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <!-- Timeline -->
        <div class="card-surface p-4">
            <h2 class="h6 mb-3"><?= e(__('crm.timeline.title')) ?></h2>
            <?php if ($activities === []): ?>
                <p class="text-muted small mb-0"><?= e(__('common.empty.message')) ?></p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($activities as $act): ?>
                        <li class="border-start ps-3 pb-3">
                            <div class="small fw-semibold"><?= e($act['title'] ?: __('crm.activity_type.' . $act['type'])) ?></div>
                            <?php if (!empty($act['description'])): ?><div class="small text-muted"><?= nl2br(e($act['description'])) ?></div><?php endif; ?>
                            <div class="text-muted" style="font-size:.72rem;"><?= e($act['created_at']) ?> <?= $act['user_name'] ? '· ' . e($act['user_name']) : '' ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
