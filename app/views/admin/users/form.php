<?php
/**
 * Admin user create/edit form.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed>|null $user
 * @var array<int,array<string,mixed>> $roles
 * @var array<string,string> $errors
 */
$user = $user ?? [];
$errors = $errors ?? [];
$id = isset($user['id']) ? (int) $user['id'] : 0;
$action = $id > 0 ? '/app/users/' . $id : '/app/users';
$val = static fn (string $k): string => e((string) ($user[$k] ?? ''));
?>
<div class="mb-4">
    <a href="/app/users" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
    <h1 class="h3 mb-0 mt-1"><?= e($id > 0 ? __('admin.users.edit') : __('admin.users.new')) ?></h1>
</div>

<div class="card-surface p-4" style="max-width:640px;">
    <form method="post" action="<?= e($action) ?>">
        <?= csrf_field() ?>
        <?php if ($id > 0): ?><?= method_field('PUT') ?><?php endif; ?>

        <div class="mb-3">
            <label class="form-label" for="name"><?= e(__('admin.users.name')) ?> *</label>
            <input type="text" class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>" id="name" name="name" value="<?= $val('name') ?>" required>
            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
        </div>

        <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="email"><?= e(__('admin.users.email')) ?> *</label>
                <input type="email" class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" value="<?= $val('email') ?>" required>
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="phone"><?= e(__('admin.users.phone')) ?></label>
                <input type="tel" class="form-control" id="phone" name="phone" value="<?= $val('phone') ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="role_id"><?= e(__('admin.users.role')) ?></label>
                <select class="form-select" id="role_id" name="role_id">
                    <option value=""><?= e(__('admin.users.no_role')) ?></option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>" <?= (int) ($user['role_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>><?= e($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6 mb-3">
                <label class="form-label" for="status"><?= e(__('admin.users.status')) ?></label>
                <select class="form-select" id="status" name="status">
                    <option value="active" <?= ($user['status'] ?? 'active') === 'active' ? 'selected' : '' ?>><?= e(__('common.status.active')) ?></option>
                    <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>><?= e(__('common.status.inactive')) ?></option>
                </select>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label" for="password">
                <?= e(__('admin.users.password')) ?> <?= $id > 0 ? '<span class="text-muted small">(' . e(__('admin.users.password_keep')) . ')</span>' : '*' ?>
            </label>
            <input type="password" class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>" id="password" name="password" autocomplete="new-password" <?= $id > 0 ? '' : 'required' ?>>
            <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-brand"><?= e(__('common.actions.save')) ?></button>
            <a href="/app/users" class="btn btn-outline-secondary"><?= e(__('common.actions.cancel')) ?></a>
        </div>
    </form>
</div>
