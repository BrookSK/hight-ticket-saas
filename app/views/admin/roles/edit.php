<?php
/**
 * Admin role permissions editor (grouped checkboxes).
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $role
 * @var array<int,array<string,mixed>> $permissions
 * @var list<int> $granted
 */
$id = (int) $role['id'];
// Group permissions by their "group" column.
$grouped = [];
foreach ($permissions as $perm) {
    $grouped[(string) $perm['group']][] = $perm;
}
$grantedMap = array_fill_keys($granted, true);
?>
<div class="mb-4">
    <a href="/app/roles" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
    <h1 class="h3 mb-0 mt-1"><?= e($role['name']) ?></h1>
    <p class="text-muted mb-0"><?= e(__('admin.roles.edit_hint')) ?></p>
</div>

<form method="post" action="/app/roles/<?= $id ?>">
    <?= csrf_field() ?>
    <?= method_field('PUT') ?>
    <div class="row g-3">
        <?php foreach ($grouped as $group => $perms): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card-surface p-3 h-100">
                    <h2 class="h6 text-capitalize"><?= e($group) ?></h2>
                    <?php foreach ($perms as $perm): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="permissions[]"
                                   value="<?= (int) $perm['id'] ?>" id="perm<?= (int) $perm['id'] ?>"
                                   <?= isset($grantedMap[(int) $perm['id']]) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="perm<?= (int) $perm['id'] ?>">
                                <code><?= e($perm['key']) ?></code>
                                <span class="text-muted d-block"><?= e($perm['description'] ?? '') ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-3">
        <button type="submit" class="btn btn-brand"><?= e(__('common.actions.save')) ?></button>
    </div>
</form>
