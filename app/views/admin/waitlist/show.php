<?php
/**
 * Admin waitlist lead detail: data, UTMs, status change, notes, timeline.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $lead
 * @var array<int,array<string,mixed>> $activities
 * @var list<string> $statuses
 */
$id = (int) $lead['id'];
$canEdit = can('waitlist.edit');
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <a href="/app/waitlist" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
        <h1 class="h3 mb-0 mt-1"><?= e($lead['name']) ?></h1>
    </div>
    <?php if ($canEdit): ?>
        <form method="post" action="/app/waitlist/<?= $id ?>/delete" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-danger"
                    data-confirm="<?= e(__('common.confirm.delete_message')) ?>">
                <i class="bi bi-trash me-1"></i><?= e(__('common.actions.delete')) ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card-surface p-4 mb-3">
            <h2 class="h6 mb-3"><?= e(__('admin.waitlist.details')) ?></h2>
            <dl class="row mb-0 small">
                <dt class="col-4 text-muted"><?= e(__('site.waitlist.field_email')) ?></dt><dd class="col-8"><?= e($lead['email']) ?></dd>
                <dt class="col-4 text-muted"><?= e(__('site.waitlist.field_phone')) ?></dt><dd class="col-8"><?= e($lead['phone']) ?></dd>
                <dt class="col-4 text-muted"><?= e(__('site.waitlist.field_company')) ?></dt><dd class="col-8"><?= e($lead['company'] ?: '—') ?></dd>
                <dt class="col-4 text-muted"><?= e(__('site.waitlist.field_sites')) ?></dt><dd class="col-8"><?= e($lead['sites_quantity'] ?: '—') ?></dd>
                <dt class="col-4 text-muted"><?= e(__('site.waitlist.field_service')) ?></dt><dd class="col-8"><?= e($lead['main_service'] ?: '—') ?></dd>
                <dt class="col-4 text-muted"><?= e(__('site.waitlist.field_message')) ?></dt><dd class="col-8"><?= nl2br(e($lead['message'] ?: '—')) ?></dd>
                <dt class="col-4 text-muted"><?= e(__('admin.waitlist.source')) ?></dt><dd class="col-8"><?= e($lead['source']) ?></dd>
                <dt class="col-4 text-muted"><?= e(__('admin.waitlist.created')) ?></dt><dd class="col-8"><?= e($lead['created_at']) ?></dd>
            </dl>
        </div>

        <div class="card-surface p-4">
            <h2 class="h6 mb-3"><?= e(__('admin.waitlist.utm')) ?></h2>
            <dl class="row mb-0 small">
                <dt class="col-4 text-muted">utm_source</dt><dd class="col-8"><?= e($lead['utm_source'] ?: '—') ?></dd>
                <dt class="col-4 text-muted">utm_medium</dt><dd class="col-8"><?= e($lead['utm_medium'] ?: '—') ?></dd>
                <dt class="col-4 text-muted">utm_campaign</dt><dd class="col-8"><?= e($lead['utm_campaign'] ?: '—') ?></dd>
                <dt class="col-4 text-muted">utm_content</dt><dd class="col-8"><?= e($lead['utm_content'] ?: '—') ?></dd>
                <dt class="col-4 text-muted">utm_term</dt><dd class="col-8"><?= e($lead['utm_term'] ?: '—') ?></dd>
            </dl>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <?php if ($canEdit): ?>
            <div class="card-surface p-4 mb-3">
                <h2 class="h6 mb-3"><?= e(__('admin.waitlist.change_status')) ?></h2>
                <form method="post" action="/app/waitlist/<?= $id ?>/status" class="d-flex gap-2">
                    <?= csrf_field() ?>
                    <select class="form-select form-select-sm" name="status">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= e($s) ?>" <?= $lead['status'] === $s ? 'selected' : '' ?>><?= e(__('admin.status.' . $s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-brand"><?= e(__('common.actions.save')) ?></button>
                </form>
            </div>

            <div class="card-surface p-4 mb-3">
                <h2 class="h6 mb-3"><?= e(__('admin.waitlist.notes')) ?></h2>
                <form method="post" action="/app/waitlist/<?= $id ?>/notes">
                    <?= csrf_field() ?>
                    <textarea class="form-control form-control-sm mb-2" name="notes" rows="4"><?= e($lead['notes'] ?? '') ?></textarea>
                    <button type="submit" class="btn btn-sm btn-brand"><?= e(__('common.actions.save')) ?></button>
                </form>
            </div>
        <?php endif; ?>

        <div class="card-surface p-4">
            <h2 class="h6 mb-3"><?= e(__('admin.waitlist.timeline')) ?></h2>
            <?php if ($activities === []): ?>
                <p class="text-muted small mb-0"><?= e(__('common.empty.message')) ?></p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($activities as $activity): ?>
                        <li class="border-start ps-3 pb-3 position-relative">
                            <div class="small fw-semibold"><?= e($activity['description'] ?: $activity['type']) ?></div>
                            <div class="text-muted" style="font-size:0.75rem;"><?= e($activity['created_at']) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
