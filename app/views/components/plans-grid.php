<?php
/**
 * Reusable plans grid. Prices/content come from the database (PlanService).
 * Expects: $plans (list of decoded plan arrays).
 * @var App\Libraries\Translator $t
 * @var array<int, array<string, mixed>> $plans
 */
$plans = $plans ?? [];
$formatPrice = static function (?string $value, string $currency): ?string {
    if ($value === null || $value === '') {
        return null;
    }
    return $currency . ' ' . number_format((float) $value, 2, ',', '.');
};
?>
<?php if ($plans === []): ?>
    <div class="empty-state">
        <i class="bi bi-tags empty-state__icon" aria-hidden="true"></i>
        <p class="mb-0"><?= e(__('site.plans.empty')) ?></p>
    </div>
<?php else: ?>
    <div class="row g-4 align-items-stretch">
        <?php foreach ($plans as $plan): ?>
            <?php
            $featured = (int) ($plan['is_featured'] ?? 0) === 1;
            $currency = (string) ($plan['currency'] ?? 'BRL');
            $monthly = $formatPrice($plan['price_monthly'] ?? null, $currency);
            ?>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card-surface p-4 plan-card<?= $featured ? ' plan-card--featured' : '' ?>">
                    <?php if ($featured): ?>
                        <span class="plan-badge"><?= e(__('site.plans.featured')) ?></span>
                    <?php endif; ?>
                    <h3 class="h5 mb-1"><?= e($plan['name']) ?></h3>
                    <p class="text-muted small"><?= e($plan['description'] ?? '') ?></p>
                    <div class="my-3">
                        <?php if ($monthly !== null): ?>
                            <span class="h4"><?= e($monthly) ?></span>
                            <span class="text-muted small">/ <?= e(__('site.plans.month')) ?></span>
                        <?php else: ?>
                            <span class="h6 text-muted"><?= e(__('site.plans.custom')) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($plan['features'])): ?>
                        <ul class="list-unstyled small flex-grow-1">
                            <?php foreach ($plan['features'] as $feature): ?>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><?= e($feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="flex-grow-1"></div>
                    <?php endif; ?>
                    <a href="<?= e($plan['cta_url'] ?: '/lista-de-espera') ?>"
                       class="btn <?= $featured ? 'btn-brand' : 'btn-outline-secondary' ?> w-100 mt-2">
                        <?= e($plan['cta_label'] ?: __('site.cta.waitlist')) ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
