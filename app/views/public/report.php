<?php
/**
 * Public commercial report page (tokenized).
 * Renders from the stored snapshot only. No internal data is queried here.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $report
 * @var array<string,mixed> $snapshot
 */
$sev = static fn (string $s): string => match ($s) {
    'critical' => 'danger', 'high' => 'warning', 'medium' => 'info', default => 'secondary',
};
?>
<section class="py-5">
    <div class="container" style="max-width: 900px;">
        <div class="text-center mb-4">
            <p class="text-muted mb-1"><?= e(__('outreach.public.diagnosis_for')) ?></p>
            <h1 class="h2 mb-1"><?= e((string) ($snapshot['company'] ?? $report['title'] ?? '')) ?></h1>
            <?php if (!empty($snapshot['domain'])): ?><p class="text-muted"><?= e((string) $snapshot['domain']) ?></p><?php endif; ?>
        </div>

        <?php if (!empty($snapshot['summary'])): ?>
            <div class="row g-3 mb-4">
                <?php foreach (['critical', 'high', 'medium', 'low'] as $level): ?>
                    <div class="col-6 col-md-3">
                        <div class="card-surface p-3 text-center">
                            <div class="h3 mb-0 text-<?= e($sev($level)) ?>"><?= (int) ($snapshot['summary'][$level] ?? 0) ?></div>
                            <div class="small text-muted"><?= e(__('outreach.public.severity.' . $level)) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($snapshot['score'])): ?>
            <div class="card-surface p-3 mb-4 text-center">
                <div class="small text-muted"><?= e(__('outreach.public.overall_score')) ?></div>
                <div class="display-5 fw-bold"><?= (int) $snapshot['score'] ?><span class="fs-5 text-muted">/100</span></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($snapshot['recommendations'])): ?>
            <div class="card-surface p-4 mb-4">
                <h2 class="h5 mb-3"><?= e(__('outreach.public.recommendations')) ?></h2>
                <?php foreach ($snapshot['recommendations'] as $rec): ?>
                    <div class="border-start border-3 border-<?= e($sev((string) ($rec['severity'] ?? 'info'))) ?> ps-3 mb-3">
                        <div class="fw-semibold"><?= e((string) ($rec['title'] ?? '')) ?></div>
                        <?php if (!empty($rec['recommendation'])): ?><div class="text-muted small"><?= e((string) $rec['recommendation']) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($report['cta_url'])): ?>
            <div class="text-center py-4">
                <a href="<?= e((string) $report['cta_url']) ?>" class="btn btn-brand btn-lg" target="_blank" rel="noopener">
                    <?= e((string) ($report['cta_label'] ?? __('outreach.public.default_cta'))) ?>
                </a>
            </div>
        <?php endif; ?>

        <p class="text-center text-muted small mt-4"><?= e(__('outreach.public.generated_by', ['name' => config_value('system_name', 'LRV Web')])) ?></p>
    </div>
</section>
