<?php
/**
 * Report detail with public link + access stats.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $report
 * @var array<string,mixed> $snapshot
 * @var string $publicUrl
 */
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <h1 class="h3 mb-0"><?= e((string) ($report['title'] ?? __('outreach.reports.report'))) ?></h1>
    <a href="/app/outreach/reports" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
</div>

<div class="card-surface p-3 mb-3">
    <label class="form-label"><?= e(__('outreach.reports.public_link')) ?></label>
    <div class="input-group">
        <input type="text" class="form-control" id="publicUrl" value="<?= e($publicUrl) ?>" readonly>
        <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('publicUrl').value); window.App.toast('success', '<?= e(__('common.copied')) ?>');"><i class="bi bi-clipboard"></i></button>
        <a class="btn btn-outline-secondary" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i></a>
    </div>
    <div class="form-text">
        <?= e(__('outreach.reports.views')) ?>: <strong><?= (int) ($report['views'] ?? 0) ?></strong>
        <?php if ($report['expires_at'] !== null): ?> · <?= e(__('outreach.reports.expires')) ?>: <?= e((string) $report['expires_at']) ?><?php endif; ?>
    </div>
</div>

<div class="card-surface p-3">
    <h2 class="h6 mb-3"><?= e(__('outreach.reports.preview')) ?></h2>
    <?php if (isset($snapshot['score'])): ?><p><?= e(__('outreach.reports.score')) ?>: <strong><?= (int) $snapshot['score'] ?></strong></p><?php endif; ?>
    <?php if (!empty($snapshot['recommendations'])): ?>
        <ul>
            <?php foreach ($snapshot['recommendations'] as $rec): ?>
                <li><strong><?= e((string) ($rec['title'] ?? '')) ?></strong> — <?= e((string) ($rec['recommendation'] ?? '')) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="text-muted mb-0"><?= e(__('outreach.reports.no_snapshot')) ?></p>
    <?php endif; ?>
</div>
