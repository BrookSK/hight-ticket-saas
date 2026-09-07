<?php
/**
 * Admin Configurações Gerais (tabbed by category).
 * @var App\Libraries\Translator $t
 * @var string $group
 * @var list<string> $groups
 * @var list<string> $keys
 * @var array<string,string> $values
 * @var list<string> $secretKeys
 */
$secretMap = array_fill_keys($secretKeys, true);
?>
<h1 class="h3 mb-4"><?= e(__('admin.settings.title')) ?></h1>

<div class="row g-3">
    <div class="col-12 col-lg-3">
        <div class="list-group">
            <?php foreach ($groups as $g): ?>
                <a href="/app/settings/<?= e($g) ?>"
                   class="list-group-item list-group-item-action <?= $g === $group ? 'active' : '' ?>">
                    <?= e(__('admin.settings.group_' . $g)) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-12 col-lg-9">
        <div class="card-surface p-4">
            <h2 class="h5 mb-3"><?= e(__('admin.settings.group_' . $group)) ?></h2>
            <?php if (can('settings.edit')): ?>
                <form method="post" action="/app/settings/<?= e($group) ?>">
                    <?= csrf_field() ?>
                    <?= method_field('PUT') ?>
                    <?php foreach ($keys as $key): ?>
                        <div class="mb-3">
                            <label class="form-label" for="<?= e($key) ?>"><?= e(__('admin.settings.field.' . $key)) ?></label>
                            <?php if (isset($secretMap[$key])): ?>
                                <input type="password" class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>"
                                       autocomplete="new-password" placeholder="<?= e(__('admin.settings.secret_ph')) ?>">
                                <div class="form-text"><?= e(__('admin.settings.secret_hint')) ?></div>
                            <?php else: ?>
                                <input type="text" class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>"
                                       value="<?= e($values[$key] ?? '') ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-brand"><?= e(__('common.actions.save')) ?></button>
                </form>
            <?php else: ?>
                <p class="text-muted mb-0"><?= e(__('errors.forbidden')) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
