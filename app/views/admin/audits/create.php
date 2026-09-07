<?php
/**
 * New audit form (URL + advanced options).
 * @var App\Libraries\Translator $t
 * @var array<string,string> $errors
 * @var array<string,mixed> $old
 */
$errors = $errors ?? [];
$old = $old ?? [];
?>
<div class="mb-4">
    <a href="/app/audits" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
    <h1 class="h3 mb-0 mt-1"><?= e(__('audit.new')) ?></h1>
    <p class="text-muted mb-0"><?= e(__('audit.new_hint')) ?></p>
</div>

<div class="card-surface p-4" style="max-width:640px;">
    <form method="post" action="/app/audits">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="url" class="form-label"><?= e(__('audit.field.url')) ?> *</label>
            <input type="url" class="form-control<?= isset($errors['url']) ? ' is-invalid' : '' ?>"
                   id="url" name="url" value="<?= e((string) ($old['url'] ?? '')) ?>"
                   placeholder="https://exemplo.com.br" required>
            <?php if (isset($errors['url'])): ?><div class="invalid-feedback"><?= e($errors['url']) ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label d-block"><?= e(__('audit.field.scope')) ?></label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="scope" id="scope_home" value="homepage"
                       <?= ($old['scope'] ?? 'homepage') !== 'full' ? 'checked' : '' ?>>
                <label class="form-check-label" for="scope_home"><?= e(__('audit.scope.homepage')) ?></label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="scope" id="scope_full" value="full"
                       <?= ($old['scope'] ?? '') === 'full' ? 'checked' : '' ?>>
                <label class="form-check-label" for="scope_full"><?= e(__('audit.scope.full')) ?></label>
            </div>
        </div>

        <details class="mb-3">
            <summary class="text-muted small mb-2" style="cursor:pointer;"><?= e(__('audit.advanced')) ?></summary>
            <div class="row mt-2">
                <div class="col-6">
                    <label for="max_pages" class="form-label small"><?= e(__('audit.field.max_pages')) ?></label>
                    <input type="number" class="form-control form-control-sm" id="max_pages" name="max_pages"
                           value="<?= e((string) ($old['max_pages'] ?? '20')) ?>" min="1" max="50">
                </div>
                <div class="col-6">
                    <label for="max_depth" class="form-label small"><?= e(__('audit.field.max_depth')) ?></label>
                    <input type="number" class="form-control form-control-sm" id="max_depth" name="max_depth"
                           value="<?= e((string) ($old['max_depth'] ?? '1')) ?>" min="0" max="2">
                </div>
            </div>
        </details>

        <button type="submit" class="btn btn-brand"><i class="bi bi-play-fill me-1"></i><?= e(__('audit.start')) ?></button>
    </form>
</div>
