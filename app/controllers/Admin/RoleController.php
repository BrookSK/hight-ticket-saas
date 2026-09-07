<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Cache;
use App\Libraries\Request;
use App\Repositories\RoleRepository;
use App\Services\ActivityLogService;
use App\Services\AuthService;

/**
 * Admin management of roles and their permissions (ACL).
 *
 * Controls flow only. Permission sync happens through the repository; the ACL
 * permission cache is cleared centrally after changes.
 */
final class RoleController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $this->render('admin.roles.index', [
            'title'      => __('admin.roles.title'),
            'activePath' => '/app/roles',
            'roles'      => $this->roles()->all(),
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function edit(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $role = $this->roles()->findById($id);
        if ($role === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $this->render('admin.roles.edit', [
            'title'       => $role['name'],
            'activePath'  => '/app/roles',
            'role'        => $role,
            'permissions' => $this->roles()->allPermissions(),
            'granted'     => $this->roles()->permissionIdsForRole($id),
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->roles()->findById($id) === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $permissionIds = $request->input('permissions', []);
        $permissionIds = is_array($permissionIds) ? array_map('intval', $permissionIds) : [];

        $this->roles()->syncPermissions($id, $permissionIds);

        // Clear ACL cache so changes take effect immediately.
        /** @var Cache $cache */
        $cache = $this->container->get('cache');
        $cache->flush();

        $this->log()->record('role_permissions_updated', $this->userId(), ['object_type' => 'role', 'object_id' => $id]);
        $this->session()->flash('status', __('admin.roles.updated'));
        $this->redirect('/app/roles/' . $id . '/edit');
    }

    private function userId(): ?int
    {
        /** @var AuthService $auth */
        $auth = $this->container->get(AuthService::class);

        return $auth->id();
    }

    private function roles(): RoleRepository
    {
        /** @var RoleRepository $repository */
        $repository = $this->container->get(RoleRepository::class);

        return $repository;
    }

    private function log(): ActivityLogService
    {
        /** @var ActivityLogService $log */
        $log = $this->container->get(ActivityLogService::class);

        return $log;
    }
}
