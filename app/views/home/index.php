<?php
/**
 * Institutional home (hero) — placeholder foundation page.
 *
 * @var App\Libraries\Translator $t
 */
?>
<section class="py-5" style="background-image: var(--gradient-brand); color: var(--color-text-inverse);">
    <div class="container py-5 text-center">
        <span class="badge-soft-primary px-3 py-2 mb-3 d-inline-block"><?= e(__('common.app_tagline')) ?></span>
        <h1 class="display-4 fw-bold mb-3"><?= e(__('common.app_name')) ?></h1>
        <p class="lead mb-4 mx-auto" style="max-width: 640px;">
            <?= e(__('common.app_tagline')) ?>.
        </p>
        <a href="/login" class="btn btn-light btn-lg fw-semibold"><?= e(__('auth.login.title')) ?></a>
    </div>
</section>

<section class="container py-5">
    <div class="row g-4">
        <div class="col-12 col-md-4">
            <div class="card-surface p-4 h-100">
                <i class="bi bi-search fs-2" style="color: var(--color-primary-600);" aria-hidden="true"></i>
                <h2 class="h5 mt-3">Auditoria</h2>
                <p class="text-muted mb-0">Analise sites e gere diagnósticos prontos para o cliente.</p>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card-surface p-4 h-100">
                <i class="bi bi-kanban fs-2" style="color: var(--color-primary-600);" aria-hidden="true"></i>
                <h2 class="h5 mt-3">CRM</h2>
                <p class="text-muted mb-0">Acompanhe leads, propostas e follow-up em um só lugar.</p>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card-surface p-4 h-100">
                <i class="bi bi-rocket-takeoff fs-2" style="color: var(--color-primary-600);" aria-hidden="true"></i>
                <h2 class="h5 mt-3">Entrega</h2>
                <p class="text-muted mb-0">Da prospecção à manutenção contínua, tudo integrado.</p>
            </div>
        </div>
    </div>
</section>
