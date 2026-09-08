<?php
/**
 * Opportunity review detail: explained opportunity score, site score, factors,
 * collected data, and actions (convert/discard/ignore).
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $result
 * @var array<int,array<string,mixed>> $factors
 */
$id = (int) $result['id'];
$opp = $result['opportunity_score'] !== null ? (int) $result['opportunity_score'] : null;
$oppColor = $opp === null ? 'var(--color-text-muted)' : ($opp >= 70 ? 'var(--color-success)' : ($opp >= 40 ? 'var(--color-warning)' : 'var(--color-text-muted)'));
$isOpen = in_array($result['status'], ['qualified', 'enriched', 'audited'], true);
$enrichment = !empty($result['enrichment']) ? json_decode((string) $result['enrichment'], true) : [];
$socials = is_array($enrichment['socials'] ?? null) ? $enrichment['socials'] : [];
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <a href="/app/prospecting/review" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
        <h1 class="h4 mb-0 mt-1"><?= e($result['name'] ?: $result['raw_name']) ?></h1>
        <?php if (!empty($result['website'])): ?><a href="<?= e($result['website']) ?>" target="_blank" rel="noopener" class="small text-muted"><?= e($result['domain'] ?: $result['website']) ?> <i class="bi bi-box-arrow-up-right"></i></a><?php endif; ?>
    </div>
    <?php if ($isOpen): ?>
        <div class="d-flex gap-2">
            <?php if (can('prospecting.convert')): ?>
                <form method="post" action="/app/prospecting/review/<?= $id ?>/convert" class="m-0"><?= csrf_field() ?>
                    <button type="submit" class="btn btn-brand"><i class="bi bi-briefcase me-1"></i><?= e(__('prospecting.review.convert')) ?></button>
                </form>
            <?php endif; ?>
        </div>
    <?php elseif ($result['status'] === 'converted'): ?>
        <span class="badge text-bg-success"><?= e(__('prospecting.review.converted_badge')) ?></span>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-5">
        <div class="card-surface p-4 text-center mb-3">
            <div class="text-muted small text-uppercase"><?= e(__('prospecting.review.opp_score')) ?></div>
            <div class="display-3 fw-bold" style="color: <?= $oppColor ?>;"><?= $opp ?? '—' ?></div>
            <div class="text-muted">/ 100 · <?= $result['opportunity_confidence'] ? e(__('audit.confidence.' . $result['opportunity_confidence'])) : '' ?></div>
            <?php if ($result['priority']): ?><span class="badge-soft-primary px-3 mt-2"><?= e(__('prospecting.priority.' . $result['priority'])) ?></span><?php endif; ?>
        </div>

        <div class="card-surface p-4 mb-3">
            <h2 class="h6 mb-2"><?= e(__('prospecting.review.why_title')) ?></h2>
            <?php if ($factors === []): ?>
                <p class="text-muted small mb-0"><?= e(__('prospecting.review.no_factors')) ?></p>
            <?php else: ?>
                <?php foreach ($factors as $f): ?>
                    <div class="d-flex justify-content-between border-bottom py-1 small">
                        <span><?= e($f['name'] ?? $f['rule_key'] ?? '') ?><?php if (!empty($f['evidence'])): ?><span class="text-muted d-block" style="font-size:.72rem;"><?= e($f['evidence']) ?></span><?php endif; ?></span>
                        <span class="fw-semibold text-success">+<?= (int) ($f['weight'] ?? 0) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($result['recommended_service'])): ?>
            <div class="card-surface p-4">
                <h2 class="h6 mb-1"><?= e(__('prospecting.review.recommended')) ?></h2>
                <p class="mb-0"><?= e(__('prospecting.service.' . $result['recommended_service'])) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card-surface p-4 mb-3">
            <h2 class="h6 mb-3"><?= e(__('prospecting.review.collected')) ?></h2>
            <dl class="row mb-0 small">
                <dt class="col-4 text-muted"><?= e(__('prospecting.review.website')) ?></dt><dd class="col-8"><?= e($result['website'] ?: __('prospecting.review.no_site')) ?> <span class="text-muted">(<?= e(__('prospecting.website_state.' . ($result['website_state'] ?: 'none'))) ?>)</span></dd>
                <dt class="col-4 text-muted"><?= e(__('prospecting.review.site_score')) ?></dt><dd class="col-8"><?= $result['site_score'] !== null ? (int) $result['site_score'] : '—' ?><?php if (!empty($result['audit_id'])): ?> · <a href="/app/audits/<?= (int) $result['audit_id'] ?>"><?= e(__('audit.report')) ?></a><?php endif; ?></dd>
                <dt class="col-4 text-muted"><?= e(__('crm.companies.phone')) ?></dt><dd class="col-8"><?= e($result['phone'] ?: '—') ?></dd>
                <dt class="col-4 text-muted">WhatsApp</dt><dd class="col-8"><?= e($result['whatsapp'] ?: '—') ?></dd>
                <dt class="col-4 text-muted">E-mail</dt><dd class="col-8"><?= e($result['email'] ?: '—') ?></dd>
                <dt class="col-4 text-muted"><?= e(__('crm.companies.city')) ?></dt><dd class="col-8"><?= e(trim(($result['city'] ?? '') . ' / ' . ($result['state'] ?? ''), ' /') ?: '—') ?></dd>
                <?php if ($socials !== []): ?>
                    <dt class="col-4 text-muted"><?= e(__('prospecting.review.socials')) ?></dt>
                    <dd class="col-8"><?php foreach ($socials as $s): ?><a href="<?= e($s) ?>" target="_blank" rel="noopener" class="d-block text-truncate"><?= e($s) ?></a><?php endforeach; ?></dd>
                <?php endif; ?>
            </dl>
            <p class="text-muted mt-2 mb-0" style="font-size:.72rem;"><?= e(__('prospecting.review.data_note')) ?></p>
        </div>

        <?php if ($isOpen): ?>
            <div class="card-surface p-4">
                <h2 class="h6 mb-3"><?= e(__('prospecting.review.other_actions')) ?></h2>
                <div class="d-flex flex-wrap gap-2">
                    <form method="post" action="/app/prospecting/review/<?= $id ?>/discard" class="m-0"><?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-secondary" data-confirm="<?= e(__('prospecting.review.confirm_discard')) ?>"><?= e(__('prospecting.review.discard')) ?></button>
                    </form>
                    <form method="post" action="/app/prospecting/review/<?= $id ?>/ignore" class="m-0"><?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="<?= e(__('prospecting.review.confirm_ignore')) ?>"><?= e(__('prospecting.review.ignore')) ?></button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
