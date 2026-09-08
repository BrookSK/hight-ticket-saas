<?php
/**
 * Lead detail: data, score/audits, status, activities/timeline, win/lose.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $lead
 * @var array<int,array<string,mixed>> $contacts
 * @var array<int,array<string,mixed>> $audits
 * @var array<int,array<string,mixed>> $activities
 * @var list<string> $statuses
 */
$id = (int) $lead['id'];
$canUpdate = can('leads.update');
$latestScore = null;
foreach ($audits as $a) { if ($a['score_overall'] !== null) { $latestScore = (int) $a['score_overall']; break; } }
$isClosed = in_array($lead['status'], ['won', 'lost'], true);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <a href="/app/leads" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
        <h1 class="h4 mb-0 mt-1"><?= e($lead['company_name']) ?><?php if (!empty($lead['title'])): ?> <span class="text-muted h6">— <?= e($lead['title']) ?></span><?php endif; ?></h1>
    </div>
    <div class="d-flex gap-2">
        <a href="/app/companies/<?= (int) $lead['company_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-building me-1"></i><?= e(__('crm.companies.title')) ?></a>
        <?php if ($canUpdate): ?><a href="/app/leads/<?= $id ?>/edit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a><?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <!-- Status + quick change -->
        <div class="card-surface p-4 mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <span class="badge-soft-primary px-2"><?= e(__('crm.status.' . $lead['status'])) ?></span>
                    <span class="ms-2 small text-muted"><?= e(__('crm.temperature.' . $lead['temperature'])) ?> · <?= e(__('crm.qualification.' . $lead['qualification'])) ?></span>
                </div>
                <?php if ($canUpdate && !$isClosed): ?>
                    <form method="post" action="/app/leads/<?= $id ?>/status" class="d-flex gap-2 m-0">
                        <?= csrf_field() ?>
                        <select class="form-select form-select-sm" name="status" style="width:auto;">
                            <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $lead['status'] === $s ? 'selected' : '' ?>><?= e(__('crm.status.' . $s)) ?></option><?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-brand"><?= e(__('common.actions.save')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Data -->
        <div class="card-surface p-4 mb-3">
            <h2 class="h6 mb-3"><?= e(__('crm.leads.details')) ?></h2>
            <dl class="row mb-0 small">
                <dt class="col-4 text-muted"><?= e(__('crm.leads.service')) ?></dt><dd class="col-8"><?= e($lead['service_type'] ?: '—') ?></dd>
                <dt class="col-4 text-muted"><?= e(__('crm.leads.value')) ?></dt><dd class="col-8"><?= $lead['estimated_value'] !== null ? e($lead['currency'] . ' ' . number_format((float) $lead['estimated_value'], 2, ',', '.')) : '—' ?></dd>
                <dt class="col-4 text-muted"><?= e(__('crm.leads.probability')) ?></dt><dd class="col-8"><?= $lead['probability'] !== null ? (int) $lead['probability'] . '%' : '—' ?></dd>
                <dt class="col-4 text-muted"><?= e(__('crm.leads.expected_close')) ?></dt><dd class="col-8"><?= e($lead['expected_close_date'] ?: '—') ?></dd>
                <dt class="col-4 text-muted"><?= e(__('crm.leads.next_step')) ?></dt><dd class="col-8"><?= e($lead['next_step'] ?: '—') ?></dd>
                <dt class="col-4 text-muted"><?= e(__('crm.leads.source')) ?></dt><dd class="col-8"><?= e($lead['source']) ?></dd>
            </dl>
        </div>

        <!-- Activities -->
        <div class="card-surface p-4">
            <h2 class="h6 mb-3"><?= e(__('crm.timeline.title')) ?></h2>

            <?php if (can('activities.create')): ?>
                <form method="post" action="/app/activities" class="row g-2 mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="lead_id" value="<?= $id ?>">
                    <input type="hidden" name="company_id" value="<?= (int) $lead['company_id'] ?>">
                    <div class="col-4">
                        <select class="form-select form-select-sm" name="type">
                            <?php foreach (['note', 'call', 'message', 'email', 'meeting', 'task'] as $tp): ?>
                                <option value="<?= $tp ?>"><?= e(__('crm.activity_type.' . $tp)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-8"><input type="text" class="form-control form-control-sm" name="title" placeholder="<?= e(__('crm.activities.title_ph')) ?>"></div>
                    <div class="col-12"><textarea class="form-control form-control-sm" name="description" rows="2" placeholder="<?= e(__('crm.activities.desc_ph')) ?>"></textarea></div>
                    <div class="col-12"><button type="submit" class="btn btn-sm btn-brand"><?= e(__('crm.activities.add')) ?></button></div>
                </form>
            <?php endif; ?>

            <?php if ($activities === []): ?>
                <p class="text-muted small mb-0"><?= e(__('common.empty.message')) ?></p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($activities as $act): ?>
                        <li class="border-start ps-3 pb-3 d-flex justify-content-between">
                            <div>
                                <div class="small fw-semibold">
                                    <i class="bi bi-dot"></i><?= e($act['title'] ?: __('crm.activity_type.' . $act['type'])) ?>
                                    <?php if ($act['type'] === 'task' && $act['status'] !== 'done'): ?><span class="badge text-bg-warning ms-1"><?= e(__('crm.task_status.' . $act['status'])) ?></span><?php endif; ?>
                                </div>
                                <?php if (!empty($act['description'])): ?><div class="small text-muted"><?= nl2br(e($act['description'])) ?></div><?php endif; ?>
                                <div class="text-muted" style="font-size:.72rem;"><?= e($act['created_at']) ?> <?= $act['user_name'] ? '· ' . e($act['user_name']) : '' ?></div>
                            </div>
                            <?php if ($act['type'] === 'task' && $act['status'] !== 'done' && can('activities.update')): ?>
                                <form method="post" action="/app/activities/<?= (int) $act['id'] ?>/complete" class="m-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="lead_id" value="<?= $id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="<?= e(__('crm.activities.complete')) ?>"><i class="bi bi-check-lg"></i></button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <!-- Site score / audits -->
        <div class="card-surface p-4 mb-3">
            <h2 class="h6 mb-3"><?= e(__('crm.leads.site_score')) ?></h2>
            <?php if ($audits === []): ?>
                <p class="text-muted small mb-0"><?= e(__('crm.leads.no_audit')) ?></p>
            <?php else: ?>
                <?php if ($latestScore !== null): ?>
                    <div class="mb-2">
                        <span class="display-6 fw-bold" style="color: <?= $latestScore >= 75 ? 'var(--color-success)' : ($latestScore >= 60 ? 'var(--color-warning)' : 'var(--color-danger)') ?>;"><?= $latestScore ?></span>
                        <span class="text-muted">/ 100</span>
                    </div>
                <?php endif; ?>
                <?php foreach ($audits as $a): ?>
                    <a href="/app/audits/<?= (int) $a['id'] ?>" class="d-flex justify-content-between border-bottom py-1 small text-decoration-none">
                        <span><?= e($a['created_at']) ?></span>
                        <span class="fw-semibold"><?= $a['score_overall'] !== null ? (int) $a['score_overall'] : '—' ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Outcome actions -->
        <?php if ($canUpdate && !$isClosed): ?>
            <div class="card-surface p-4 mb-3">
                <h2 class="h6 mb-3"><?= e(__('crm.leads.outcome')) ?></h2>
                <form method="post" action="/app/leads/<?= $id ?>/win" class="mb-2">
                    <?= csrf_field() ?>
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control" name="final_value" placeholder="<?= e(__('crm.leads.final_value')) ?>">
                    </div>
                    <button type="submit" class="btn btn-sm btn-success w-100" data-confirm="<?= e(__('crm.leads.confirm_win')) ?>"><i class="bi bi-trophy me-1"></i><?= e(__('crm.leads.mark_won')) ?></button>
                </form>
                <form method="post" action="/app/leads/<?= $id ?>/lose">
                    <?= csrf_field() ?>
                    <select class="form-select form-select-sm mb-2" name="loss_reason">
                        <?php foreach (['price', 'competitor', 'no_budget', 'no_response', 'cancelled', 'no_need', 'other'] as $r): ?>
                            <option value="<?= $r ?>"><?= e(__('crm.loss_reason.' . $r)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100" data-confirm="<?= e(__('crm.leads.confirm_lose')) ?>"><?= e(__('crm.leads.mark_lost')) ?></button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Contacts quick view -->
        <div class="card-surface p-4">
            <h2 class="h6 mb-3"><?= e(__('crm.contacts.title')) ?></h2>
            <?php if ($contacts === []): ?>
                <p class="text-muted small mb-0"><?= e(__('crm.contacts.empty')) ?></p>
            <?php else: ?>
                <?php foreach ($contacts as $contact): ?>
                    <div class="small border-bottom py-1">
                        <span class="fw-semibold"><?= e(trim($contact['first_name'] . ' ' . ($contact['last_name'] ?? ''))) ?></span>
                        <?php if ((int) $contact['is_primary'] === 1): ?><i class="bi bi-star-fill text-warning ms-1" title="<?= e(__('crm.contacts.primary')) ?>"></i><?php endif; ?>
                        <div class="text-muted"><?= e($contact['email'] ?? '') ?> <?= $contact['phone'] ? '· ' . e($contact['phone']) : '' ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
