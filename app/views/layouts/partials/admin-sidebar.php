<?php
/**
 * Admin sidebar navigation (permission-aware).
 * Links only appear when the current user has the required permission.
 * @var App\Libraries\Translator $t
 * @var string $activePath Current admin path for active highlighting.
 */
$activePath = $activePath ?? '';
$items = [
    ['/app',           'bi-speedometer2', __('admin.nav.dashboard'), 'dashboard.view'],
    ['/app/waitlist',  'bi-people',       __('admin.nav.waitlist'),  'waitlist.view'],
    ['/app/plans',     'bi-tags',         __('admin.nav.plans'),     'plans.view'],
    ['/app/users',     'bi-person-gear',  __('admin.nav.users'),     'users.view'],
    ['/app/roles',     'bi-shield-lock',  __('admin.nav.roles'),     'roles.view'],
    ['/app/settings',  'bi-gear',         __('admin.nav.settings'),  'settings.view'],
    ['/app/logs',      'bi-clock-history', __('admin.nav.logs'),     'logs.view'],
];
?>
<ul class="nav nav-pills flex-column gap-1">
    <?php foreach ($items as [$href, $icon, $label, $permission]): ?>
        <?php if (can($permission)): ?>
            <li class="nav-item">
                <a class="nav-link<?= $activePath === $href ? ' active' : '' ?>" href="<?= e($href) ?>">
                    <i class="bi <?= e($icon) ?> me-2" aria-hidden="true"></i><?= e($label) ?>
                </a>
            </li>
        <?php endif; ?>
    <?php endforeach; ?>
</ul>
