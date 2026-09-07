<?php
/**
 * Audit dashboard / report.
 * Shows live progress while processing; the full report once completed/partial.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $audit
 * @var array<int,array<string,mixed>> $issues
 * @var array<string,int> $severityCounts
 * @var array<int,array<string,mixed>> $metrics
 * @var array<int,array<string,mixed>> $technologies
 * @var array<int,array<string,mixed>> $contacts
 * @var array<int,array<string,mixed>> $pages
 * @var array<string,mixed>|null $summary
 */
$id = (int) $audit['id'];
$status = (string) $audit['status'];
$isProcessing = in_array($status, ['queued', 'processing'], true);
$isDone = in_array($status, ['completed', 'partial'], true);

$scoreColor = static function (?int $s): string {
    if ($s === null) { return 'var(--color-text-muted)'; }
    return $s >= 75 ? 'var(--color-success)' : ($s >= 60 ? 'var(--color-warning)' : 'var(--color-danger)');
};
$categories = [
    'performance'    => __('audit.category.performance'),
    'seo'            => __('audit.category.seo'),
    'security'       => __('audit.category.security'),
    'accessibility'  => __('audit.category.accessibility'),
    'content'        => __('audit.category.content'),
    'best_practices' => __('audit.category.best_practices'),
];
$positives = is_array($summary['positives'] ?? null) ? $summary['positives'] : [];
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <a href="/app/audits" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
        <h1 class="h4 mb-0 mt-1"><?= e($audit['host']) ?></h1>
        <a href="<?= e($audit['normalized_url']) ?>" target="_blank" rel="noopener" class="small text-muted"><?= e($audit['normalized_url']) ?> <i class="bi bi-box-arrow-up-right"></i></a>
    </div>
    <div class="d-flex gap-2">
        <?php if ($isDone && can('audits.export')): ?>
            <a href="/app/audits/<?= $id ?>/report" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-text me-1"></i><?= e(__('audit.report')) ?>
            </a>
            <a href="/app/audits/<?= $id ?>/report?pdf=1" target="_blank" rel="noopener" class="btn btn-brand">
                <i class="bi bi-download me-1"></i>PDF
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($isProcessing): ?>
    <!-- Live progress (polling) -->
    <div class="card-surface p-5 text-center" id="auditProgress" data-audit-id="<?= $id ?>">
        <div class="spinner-border mb-3" role="status" style="color:var(--color-primary-600);"></div>
        <h2 class="h5" id="progressStep"><?= e(__('audit.status.' . $status)) ?></h2>
        <div class="progress mt-3 mx-auto" style="max-width:420px;height:10px;">
            <div class="progress-bar" id="progressBar" role="progressbar"
                 style="width: <?= (int) $audit['progress'] ?>%; background-image: var(--gradient-brand);"
                 aria-valuenow="<?= (int) $audit['progress'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <p class="text-muted small mt-2"><span id="progressPct"><?= (int) $audit['progress'] ?></span>% · <span id="progressPages"><?= (int) $audit['pages_crawled'] ?></span> <?= e(__('audit.pages')) ?></p>
        <p class="text-muted small mb-0"><?= e(__('audit.processing_note')) ?></p>
    </div>

    <script>
    (function () {
        var box = document.getElementById('auditProgress');
        if (!box) { return; }
        var id = box.getAttribute('data-audit-id');
        var steps = <?= json_encode([
            'validating_domain' => __('audit.step.validating_domain'),
            'crawling'          => __('audit.step.crawling'),
            'analyzing'         => __('audit.step.analyzing'),
            'scoring'           => __('audit.step.scoring'),
            'persisting'        => __('audit.step.persisting'),
        ], JSON_UNESCAPED_UNICODE) ?>;
        function poll() {
            fetch('/app/audits/' + id + '/progress', { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (!res || !res.success) { return; }
                    var d = res.data;
                    document.getElementById('progressBar').style.width = d.progress + '%';
                    document.getElementById('progressPct').textContent = d.progress;
                    document.getElementById('progressPages').textContent = d.pages;
                    if (d.current_step && steps[d.current_step]) {
                        document.getElementById('progressStep').textContent = steps[d.current_step];
                    }
                    if (d.finished) { window.location.reload(); return; }
                    setTimeout(poll, 2500);
                })
                .catch(function () { setTimeout(poll, 4000); });
        }
        setTimeout(poll, 2500);
    })();
    </script>

<?php elseif ($status === 'failed'): ?>
    <div class="card-surface p-5 text-center">
        <i class="bi bi-exclamation-triangle text-danger" style="font-size:2.5rem;"></i>
        <h2 class="h5 mt-3"><?= e(__('audit.failed_title')) ?></h2>
        <p class="text-muted"><?= e(__('audit.failed_text')) ?></p>
    </div>

<?php else: ?>
    <!-- Report -->
    <?php if ($status === 'partial'): ?>
        <div class="alert alert-warning" role="alert">
            <i class="bi bi-info-circle me-1"></i><?= e(__('audit.partial_note')) ?>
        </div>
    <?php endif; ?>

    <!-- Executive summary + scores -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card-surface p-4 text-center h-100">
                <div class="text-muted small text-uppercase"><?= e(__('audit.overall_score')) ?></div>
                <div class="display-3 fw-bold" style="color: <?= $scoreColor($audit['score_overall'] !== null ? (int) $audit['score_overall'] : null) ?>;">
                    <?= $audit['score_overall'] !== null ? (int) $audit['score_overall'] : '—' ?>
                </div>
                <div class="text-muted">/ 100</div>
            </div>
        </div>
        <div class="col-12 col-lg-8">
            <div class="card-surface p-4 h-100">
                <div class="row g-3">
                    <?php foreach ($categories as $key => $label): ?>
                        <?php $val = $audit['score_' . $key] !== null ? (int) $audit['score_' . $key] : null; ?>
                        <div class="col-6 col-md-4">
                            <div class="text-muted small"><?= e($label) ?></div>
                            <div class="h4 mb-1" style="color: <?= $scoreColor($val) ?>;"><?= $val !== null ? $val : '—' ?></div>
                            <div class="mockup-bar-row"><span style="width: <?= (int) ($val ?? 0) ?>%;"></span></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Issue severity counters -->
    <div class="row g-2 mb-4">
        <?php foreach (['critical' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'secondary', 'info' => 'light'] as $sev => $variant): ?>
            <div class="col">
                <div class="card-surface p-3 text-center">
                    <div class="h4 mb-0"><?= (int) ($severityCounts[$sev] ?? 0) ?></div>
                    <div class="text-muted small"><?= e(__('audit.severity.' . $sev)) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <!-- Positives -->
            <?php if ($positives !== []): ?>
                <div class="card-surface p-4 mb-3">
                    <h2 class="h6 mb-3"><i class="bi bi-check-circle text-success me-1"></i><?= e(__('audit.positives')) ?></h2>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($positives as $p): ?>
                            <span class="badge text-bg-light border"><?= e($p) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Issues with filters -->
            <div class="card-surface p-4">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                    <h2 class="h6 mb-0"><?= e(__('audit.issues')) ?></h2>
                    <input type="search" class="form-control form-control-sm" id="issueSearch"
                           placeholder="<?= e(__('audit.search_issues')) ?>" style="max-width:220px;">
                </div>
                <div class="d-flex flex-wrap gap-1 mb-3" id="issueFilters">
                    <button class="btn btn-sm btn-outline-secondary active" data-filter="all"><?= e(__('audit.filter.all')) ?></button>
                    <?php foreach (['critical', 'high', 'medium', 'low', 'info'] as $sev): ?>
                        <button class="btn btn-sm btn-outline-secondary" data-filter="<?= $sev ?>"><?= e(__('audit.severity.' . $sev)) ?></button>
                    <?php endforeach; ?>
                </div>

                <?php if ($issues === []): ?>
                    <div class="empty-state"><i class="bi bi-emoji-smile empty-state__icon"></i><p class="mb-0"><?= e(__('audit.no_issues')) ?></p></div>
                <?php else: ?>
                    <div id="issueList">
                        <?php foreach ($issues as $issue): ?>
                            <?php
                            $sevVariant = ['critical' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'secondary', 'info' => 'light'][$issue['severity']] ?? 'secondary';
                            $evidence = $issue['evidence'] ? json_decode((string) $issue['evidence'], true) : null;
                            ?>
                            <div class="border rounded p-3 mb-2 issue-item"
                                 data-severity="<?= e($issue['severity']) ?>"
                                 data-text="<?= e(mb_strtolower($issue['title'] . ' ' . ($issue['page_url'] ?? ''))) ?>">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div>
                                        <span class="badge text-bg-<?= $sevVariant ?> me-1"><?= e(__('audit.severity.' . $issue['severity'])) ?></span>
                                        <span class="badge text-bg-light border"><?= e(__('audit.category.' . $issue['category'])) ?></span>
                                        <?php if ($issue['confidence'] !== 'high'): ?>
                                            <span class="badge text-bg-light border text-muted"><?= e(__('audit.confidence.' . $issue['confidence'])) ?></span>
                                        <?php endif; ?>
                                        <div class="fw-semibold mt-2"><?= e($issue['title']) ?></div>
                                    </div>
                                </div>
                                <?php if (!empty($issue['description'])): ?><p class="small text-muted mb-1 mt-2"><?= e($issue['description']) ?></p><?php endif; ?>
                                <?php if (!empty($issue['impact'])): ?><p class="small mb-1"><strong><?= e(__('audit.impact')) ?>:</strong> <?= e($issue['impact']) ?></p><?php endif; ?>
                                <?php if (!empty($issue['recommendation'])): ?><p class="small mb-0"><strong><?= e(__('audit.recommendation')) ?>:</strong> <?= e($issue['recommendation']) ?></p><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <!-- Technologies -->
            <div class="card-surface p-4 mb-3">
                <h2 class="h6 mb-3"><?= e(__('audit.technologies')) ?></h2>
                <?php if ($technologies === []): ?>
                    <p class="text-muted small mb-0"><?= e(__('audit.not_available')) ?></p>
                <?php else: ?>
                    <?php foreach ($technologies as $tech): ?>
                        <div class="d-flex justify-content-between border-bottom py-1 small">
                            <span><?= e($tech['name']) ?> <span class="text-muted">(<?= e($tech['category'] ?? '—') ?>)</span></span>
                            <span class="text-muted"><?= e(__('audit.confidence.' . $tech['confidence'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Contacts -->
            <div class="card-surface p-4 mb-3">
                <h2 class="h6 mb-3"><?= e(__('audit.contacts')) ?></h2>
                <?php if ($contacts === []): ?>
                    <p class="text-muted small mb-0"><?= e(__('audit.not_available')) ?></p>
                <?php else: ?>
                    <?php foreach ($contacts as $contact): ?>
                        <div class="small border-bottom py-1 text-truncate"><i class="bi bi-dot"></i><?= e($contact['value']) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Core Web Vitals (not available in this phase) -->
            <div class="card-surface p-4">
                <h2 class="h6 mb-3">Core Web Vitals</h2>
                <?php foreach (['LCP', 'CLS', 'INP'] as $vital): ?>
                    <div class="d-flex justify-content-between small border-bottom py-1">
                        <span><?= $vital ?></span><span class="text-muted"><?= e(__('audit.not_available')) ?></span>
                    </div>
                <?php endforeach; ?>
                <p class="text-muted mt-2 mb-0" style="font-size:0.72rem;"><?= e(__('audit.cwv_note')) ?></p>
            </div>
        </div>
    </div>

    <script>
    // Issue filtering (severity + text search).
    (function () {
        var filters = document.getElementById('issueFilters');
        var search = document.getElementById('issueSearch');
        var items = Array.prototype.slice.call(document.querySelectorAll('.issue-item'));
        var current = 'all';
        function apply() {
            var q = (search && search.value || '').toLowerCase();
            items.forEach(function (el) {
                var okSev = current === 'all' || el.getAttribute('data-severity') === current;
                var okText = q === '' || el.getAttribute('data-text').indexOf(q) !== -1;
                el.style.display = (okSev && okText) ? '' : 'none';
            });
        }
        if (filters) {
            filters.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-filter]');
                if (!btn) { return; }
                filters.querySelectorAll('button').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                current = btn.getAttribute('data-filter');
                apply();
            });
        }
        if (search) { search.addEventListener('input', apply); }
    })();
    </script>
<?php endif; ?>
