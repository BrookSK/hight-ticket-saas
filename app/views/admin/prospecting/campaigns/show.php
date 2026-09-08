<?php
/**
 * Campaign detail: status/actions, progress (polling), funnel + counters.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $campaign
 * @var array<string,int> $funnel
 */
$id = (int) $campaign['id'];
$status = (string) $campaign['status'];
$isRunning = in_array($status, ['scheduled', 'processing'], true);
$canRun = in_array($status, ['draft', 'paused', 'completed', 'partial'], true);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <a href="/app/prospecting/campaigns" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
        <h1 class="h4 mb-0 mt-1"><?= e($campaign['name']) ?></h1>
        <span class="badge-soft-primary px-2"><?= e(__('prospecting.status.' . $status)) ?></span>
    </div>
    <div class="d-flex gap-2">
        <?php if ($canRun && can('prospecting.run')): ?>
            <form method="post" action="/app/prospecting/campaigns/<?= $id ?>/run" class="m-0"><?= csrf_field() ?>
                <button type="submit" class="btn btn-brand"><i class="bi bi-play-fill me-1"></i><?= e(__('prospecting.campaigns.run')) ?></button>
            </form>
        <?php endif; ?>
        <?php if ($isRunning && can('prospecting.pause')): ?>
            <form method="post" action="/app/prospecting/campaigns/<?= $id ?>/pause" class="m-0"><?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-pause-fill me-1"></i><?= e(__('prospecting.campaigns.pause_action')) ?></button>
            </form>
        <?php endif; ?>
        <a href="/app/prospecting/review?campaign_id=<?= $id ?>" class="btn btn-outline-secondary"><i class="bi bi-list-check me-1"></i><?= e(__('prospecting.review.title')) ?></a>
    </div>
</div>

<div class="row g-3 mb-3" id="campaignBox" data-id="<?= $id ?>" data-running="<?= $isRunning ? '1' : '0' ?>">
    <div class="col-12">
        <div class="card-surface p-4">
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span id="stepLabel"><?= e($campaign['current_step'] ? __('prospecting.step.' . $campaign['current_step']) : __('prospecting.status.' . $status)) ?></span>
                <span><span id="pct"><?= (int) $campaign['progress'] ?></span>%</span>
            </div>
            <div class="progress" style="height:10px;">
                <div class="progress-bar" id="bar" style="width: <?= (int) $campaign['progress'] ?>%; background-image: var(--gradient-brand);"></div>
            </div>
        </div>
    </div>
</div>

<!-- Funnel -->
<div class="row g-2">
    <?php
    $steps = [
        'count_discovered' => __('prospecting.funnel.discovered'),
        'count_duplicated' => __('prospecting.funnel.duplicated'),
        'count_enriched'   => __('prospecting.funnel.enriched'),
        'count_audited'    => __('prospecting.funnel.audited'),
        'count_qualified'  => __('prospecting.funnel.qualified'),
        'count_converted'  => __('prospecting.funnel.converted'),
    ];
    foreach ($steps as $key => $label): ?>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card-surface p-3 text-center">
                <div class="h4 mb-0"><?= (int) ($campaign[$key] ?? 0) ?></div>
                <div class="text-muted small"><?= e($label) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
(function () {
    var box = document.getElementById('campaignBox');
    if (!box || box.getAttribute('data-running') !== '1') { return; }
    var id = box.getAttribute('data-id');
    var steps = <?= json_encode([
        'queued'      => __('prospecting.step.queued'),
        'discovering' => __('prospecting.step.discovering'),
        'enriching'   => __('prospecting.step.enriching'),
        'scoring'     => __('prospecting.step.scoring'),
    ], JSON_UNESCAPED_UNICODE) ?>;
    function poll() {
        fetch('/app/prospecting/campaigns/' + id + '/progress', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res || !res.success) { return; }
                var d = res.data;
                document.getElementById('bar').style.width = d.progress + '%';
                document.getElementById('pct').textContent = d.progress;
                if (d.step && steps[d.step]) { document.getElementById('stepLabel').textContent = steps[d.step]; }
                if (d.finished) { window.location.reload(); return; }
                setTimeout(poll, 3000);
            })
            .catch(function () { setTimeout(poll, 5000); });
    }
    setTimeout(poll, 3000);
})();
</script>
