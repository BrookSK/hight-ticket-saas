<?php
/**
 * Forgot password form.
 * @var App\Libraries\Translator $t
 * @var string|null $error
 * @var string|null $success
 */
?>
<section class="d-flex align-items-center justify-content-center" style="min-height: calc(100vh - var(--navbar-height));">
    <div class="card-surface p-4 p-md-5" style="width: 100%; max-width: 420px;">
        <h1 class="h4 mb-2 text-center"><?= e(__('auth.forgot.title')) ?></h1>
        <p class="text-muted text-center mb-4"><?= e(__('auth.forgot.subtitle')) ?></p>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/recuperar-senha" novalidate>
            <?= csrf_field() ?>
            <div class="mb-4">
                <label for="email" class="form-label"><?= e(__('auth.login.email')) ?></label>
                <input type="email" class="form-control" id="email" name="email"
                       placeholder="<?= e(__('auth.login.placeholder.email')) ?>" autocomplete="email" required>
            </div>
            <button type="submit" class="btn btn-brand w-100"><?= e(__('auth.forgot.submit')) ?></button>
        </form>
        <p class="text-center mt-3 mb-0">
            <a href="/login"><?= e(__('common.actions.back')) ?></a>
        </p>
    </div>
</section>
