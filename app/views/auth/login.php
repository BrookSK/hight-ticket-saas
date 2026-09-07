<?php
/**
 * Login form.
 *
 * @var App\Libraries\Translator $t
 * @var string|null $error
 * @var string|null $oldEmail
 */
?>
<section class="d-flex align-items-center justify-content-center" style="min-height: calc(100vh - var(--navbar-height));">
    <div class="card-surface p-4 p-md-5" style="width: 100%; max-width: 420px;">
        <h1 class="h4 mb-4 text-center"><?= e(__('auth.login.title')) ?></h1>

        <?php $status = app('session')->getFlash('status'); ?>
        <?php if (!empty($status)): ?>
            <div class="alert alert-success" role="alert"><?= e($status) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/login" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="email" class="form-label"><?= e(__('auth.login.email')) ?></label>
                <input type="email" class="form-control" id="email" name="email"
                       value="<?= e($oldEmail ?? '') ?>"
                       placeholder="<?= e(__('auth.login.placeholder.email')) ?>"
                       autocomplete="email" required>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label"><?= e(__('auth.login.password')) ?></label>
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="<?= e(__('auth.login.placeholder.password')) ?>"
                       autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-brand w-100"><?= e(__('auth.login.submit')) ?></button>
        </form>
        <p class="text-center mt-3 mb-0">
            <a href="/recuperar-senha"><?= e(__('auth.forgot.link')) ?></a>
        </p>
    </div>
</section>
