<?php
/**
 * Audit report — single, print-ready HTML (source of truth for the PDF).
 *
 * Self-contained inline CSS so it renders identically in the browser (Ctrl+P →
 * save as PDF) and via Dompdf. Uses account branding from settings; shows only
 * public data collected during the audit.
 *
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $audit
 * @var array<int,array<string,mixed>> $issues
 * @var array<string,int> $severityCounts
 * @var array<int,array<string,mixed>> $technologies
 * @var array<string,mixed>|null $summary
 * @var string $brandName
 * @var string|null $brandLogo
 * @var string|null $brandContact
 * @var bool $pdfMode Whether rendered for Dompdf (hides browser-only UI).
 */
$positives = is_array($summary['positives'] ?? null) ? $summary['positives'] : [];
$scoreColor = static function (?int $s): string {
    if ($s === null) { return '#64748b'; }
    return $s >= 75 ? '#16a34a' : ($s >= 60 ? '#d97706' : '#dc2626');
};
$categories = [
    'performance'    => __('audit.category.performance'),
    'seo'            => __('audit.category.seo'),
    'security'       => __('audit.category.security'),
    'accessibility'  => __('audit.category.accessibility'),
    'content'        => __('audit.category.content'),
    'best_practices' => __('audit.category.best_practices'),
];
$overall = $audit['score_overall'] !== null ? (int) $audit['score_overall'] : null;
$sevColors = ['critical' => '#dc2626', 'high' => '#d97706', 'medium' => '#2563eb', 'low' => '#64748b', 'info' => '#94a3b8'];
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title><?= e(__('audit.report_title', ['host' => $audit['host']])) ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color: #0f172a; margin: 0; font-size: 13px; }
    .page { padding: 32px 40px; }
    .cover { background: linear-gradient(135deg,#6d28d9,#a78bfa); color:#fff; padding: 60px 40px; }
    .cover h1 { font-size: 30px; margin: 8px 0; }
    .muted { color: #64748b; }
    .brand { display:flex; align-items:center; gap:10px; font-weight:700; font-size:16px; }
    h2 { font-size: 17px; border-bottom: 2px solid #ede9fe; padding-bottom: 6px; margin-top: 28px; }
    .grid { width:100%; }
    .score-hero { font-size: 56px; font-weight: 800; }
    table { width:100%; border-collapse: collapse; }
    td, th { text-align: left; padding: 6px 8px; vertical-align: top; }
    .cat { display:inline-block; width: 30%; margin: 4px 0; }
    .bar { height: 8px; background:#ede9fe; border-radius: 999px; overflow:hidden; }
    .bar > span { display:block; height:100%; background: linear-gradient(135deg,#7c3aed,#a78bfa); }
    .issue { border:1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; margin: 8px 0; page-break-inside: avoid; }
    .tag { display:inline-block; padding:1px 8px; border-radius: 999px; color:#fff; font-size: 11px; }
    .pos { display:inline-block; background:#dcfce7; color:#166534; border-radius:999px; padding:2px 10px; margin:2px; font-size:11px; }
    .footer { margin-top: 30px; padding-top: 12px; border-top:1px solid #e2e8f0; color:#64748b; font-size: 11px; }
    @media print { .no-print { display:none !important; } }
</style>
</head>
<body>

<!-- Cover -->
<div class="cover">
    <div class="brand">
        <?php if (!empty($brandLogo)): ?><img src="<?= e($brandLogo) ?>" alt="" style="height:32px;"><?php else: ?>◆<?php endif; ?>
        <span><?= e($brandName) ?></span>
    </div>
    <h1><?= e(__('audit.report_heading')) ?></h1>
    <div style="font-size:16px;"><?= e($audit['host']) ?></div>
    <div style="opacity:.85; margin-top:8px;"><?= e($audit['normalized_url']) ?></div>
    <div style="opacity:.85;"><?= e(__('audit.report_date', ['date' => date('d/m/Y', strtotime((string) $audit['created_at']))])) ?></div>
</div>

<div class="page">
    <?php if (!$pdfMode): ?>
        <div class="no-print" style="margin-bottom:16px;">
            <button onclick="window.print()" style="background:#7c3aed;color:#fff;border:none;padding:10px 16px;border-radius:8px;cursor:pointer;">
                <?= e(__('audit.print_save_pdf')) ?>
            </button>
        </div>
    <?php endif; ?>

    <!-- Executive summary -->
    <h2><?= e(__('audit.exec_summary')) ?></h2>
    <table><tr>
        <td style="width:180px;">
            <div class="muted"><?= e(__('audit.overall_score')) ?></div>
            <div class="score-hero" style="color: <?= $scoreColor($overall) ?>;"><?= $overall ?? '—' ?></div>
            <div class="muted">/ 100 — <?= e(__('audit.band.' . (new \App\Libraries\Audit\ScoringEngine())->band((int) ($overall ?? 0)))) ?></div>
        </td>
        <td>
            <p><?= e(__('audit.exec_intro', [
                'count' => array_sum($severityCounts),
                'host'  => $audit['host'],
            ])) ?></p>
            <div>
                <?php foreach ($categories as $key => $label): ?>
                    <?php $val = $audit['score_' . $key] !== null ? (int) $audit['score_' . $key] : 0; ?>
                    <div class="cat">
                        <div class="muted" style="font-size:11px;"><?= e($label) ?> — <?= $val ?></div>
                        <div class="bar"><span style="width: <?= $val ?>%;"></span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </td>
    </tr></table>

    <!-- Positives -->
    <?php if ($positives !== []): ?>
        <h2><?= e(__('audit.positives')) ?></h2>
        <?php foreach ($positives as $p): ?><span class="pos"><?= e($p) ?></span><?php endforeach; ?>
    <?php endif; ?>

    <!-- Issues by severity -->
    <?php foreach (['critical', 'high', 'medium', 'low', 'info'] as $sev): ?>
        <?php
        $group = array_values(array_filter($issues, static fn ($i): bool => $i['severity'] === $sev));
        if ($group === []) { continue; }
        ?>
        <h2><span class="tag" style="background: <?= $sevColors[$sev] ?>;"><?= e(__('audit.severity.' . $sev)) ?></span></h2>
        <?php foreach ($group as $issue): ?>
            <div class="issue">
                <strong><?= e($issue['title']) ?></strong>
                <span class="muted" style="font-size:11px;">· <?= e(__('audit.category.' . $issue['category'])) ?><?php if ($issue['confidence'] !== 'high'): ?> · <?= e(__('audit.confidence.' . $issue['confidence'])) ?><?php endif; ?></span>
                <?php if (!empty($issue['impact'])): ?><div style="margin-top:4px;"><em><?= e(__('audit.impact')) ?>:</em> <?= e($issue['impact']) ?></div><?php endif; ?>
                <?php if (!empty($issue['recommendation'])): ?><div><em><?= e(__('audit.recommendation')) ?>:</em> <?= e($issue['recommendation']) ?></div><?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <!-- Technologies -->
    <?php if ($technologies !== []): ?>
        <h2><?= e(__('audit.technologies')) ?></h2>
        <table>
            <?php foreach ($technologies as $tech): ?>
                <tr>
                    <td><?= e($tech['name']) ?></td>
                    <td class="muted"><?= e($tech['category'] ?? '—') ?></td>
                    <td class="muted"><?= e(__('audit.confidence.' . $tech['confidence'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <!-- Conclusion + CTA -->
    <h2><?= e(__('audit.conclusion')) ?></h2>
    <p><?= e(__('audit.conclusion_text')) ?></p>
    <?php if (!empty($brandContact)): ?>
        <p><strong><?= e(__('audit.cta_report')) ?></strong><br><?= e($brandContact) ?></p>
    <?php endif; ?>

    <div class="footer">
        <?= e(__('audit.report_footer', ['brand' => $brandName])) ?> ·
        <?= e(__('audit.report_public_note')) ?>
    </div>
</div>
</body>
</html>
