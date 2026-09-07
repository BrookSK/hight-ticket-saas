<?php
/**
 * Reset password form.
 * @var App\Libraries\Translator $t
 * @var string $email
 * @var string $token
 * @var string|null $error
 */
?>
<section class="d-flex align-items-center justify-content-center" style="min-height: calc(100vh - var(--navbar-height));">
    <div class="card-surface p-4 p-md-5" style="width: 100%; max-width: 420px;">
        <h1 class="h4 mb-4 text-center"><?= e(__('auth.reset.title')) ?></h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/redefinir-senha" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="email" value="<?= e($email ?? '') ?>">
            <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
            <div class="mb-3">
                <label for="password" class="form-label"><?= e(__('auth.reset.new_password')) ?></label>
                <input type="password" class="form-control" id="password" name="password"
                       autocomplete="new-password" minlength="8" required>
            </div>
            <div class="mb-4">
                <label for="password_confirmation" class="form-label"><?= e(__('auth.reset.confirm_password')) ?></label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                       autocomplete="new-password" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-brand w-100"><?= e(__('auth.reset.submit')) ?></button>
        </form>
    </div>
</section>
