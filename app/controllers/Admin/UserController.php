<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\UserService;

/**
 * Admin management of users. Controls flow only; UserService holds the rules.
 */
final class UserController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $this->render('admin.users.index', [
            'title'      => __('admin.users.title'),
            'activePath' => '/app/users',
            'users'      => $this->users()->allWithRole(),
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function create(Request $request, array $params = []): void
    {
        $this->render('admin.users.form', [
            'title'      => __('admin.users.new'),
            'activePath' => '/app/users',
            'user'       => null,
            'roles'      => $this->roles()->all(),
            'errors'     => [],
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): void
    {
        $result = $this->userService()->create($request->all());
        if (!($result['ok'] ?? false)) {
            $this->render('admin.users.form', [
                'title'      => __('admin.users.new'),
                'activePath' => '/app/users',
                'user'       => $request->all(),
                'roles'      => $this->roles()->all(),
                'errors'     => $this->translate($result['errors'] ?? []),
            ], 'admin', 422);

            return;
        }

        $this->log()->record('user_created', $this->userId(), ['object_type' => 'user', 'object_id' => $result['id'] ?? null]);
        $this->session()->flash('status', __('admin.users.created'));
        $this->redirect('/app/users');
    }

    /**
     * @param array<string, string> $params
     */
    public function edit(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $user = $this->users()->findById($id);
        if ($user === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $this->render('admin.users.form', [
            'title'      => __('admin.users.edit'),
            'activePath' => '/app/users',
            'user'       => $user,
            'roles'      => $this->roles()->all(),
            'errors'     => [],
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $result = $this->userService()->update($id, $request->all());
        if (!($result['ok'] ?? false)) {
            $user = $request->all();
            $user['id'] = $id;
            $this->render('admin.users.form', [
                'title'      => __('admin.users.edit'),
                'activePath' => '/app/users',
                'user'       => $user,
                'roles'      => $this->roles()->all(),
                'errors'     => $this->translate($result['errors'] ?? []),
            ], 'admin', 422);

            return;
        }

        $this->log()->record('user_updated', $this->userId(), ['object_type' => 'user', 'object_id' => $id]);
        $this->session()->flash('status', __('admin.users.updated'));
        $this->redirect('/app/users');
    }

    /**
     * @param array<string, string> $params
     */
    public function delete(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);

        // Prevent self-deletion.
        if ($id === $this->userId()) {
            $this->session()->flash('status', __('admin.users.cannot_delete_self'));
            $this->redirect('/app/users');

            return;
        }

        if ($this->userService()->delete($id)) {
            $this->log()->record('user_deleted', $this->userId(), ['object_type' => 'user', 'object_id' => $id]);
            $this->session()->flash('status', __('admin.users.deleted'));
        }

        $this->redirect('/app/users');
    }

    /**
     * Start impersonating a user (Super Admin only).
     *
     * @param array<string, string> $params
     */
    public function impersonate(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        /** @var AuthService $auth */
        $auth = $this->container->get(AuthService::class);

        if ($auth->impersonate($id)) {
            $this->log()->record('impersonation_started', $this->userId(), ['object_type' => 'user', 'object_id' => $id]);
        }

        $this->redirect('/app');
    }

    /**
     * Stop impersonating and return to the original account.
     *
     * @param array<string, string> $params
     */
    public function stopImpersonation(Request $request, array $params = []): void
    {
        /** @var AuthService $auth */
        $auth = $this->container->get(AuthService::class);

        if ($auth->stopImpersonating()) {
            $this->log()->record('impersonation_stopped', $this->userId());
        }

        $this->redirect('/app');
    }

    /**
     * @param array<string, string> $errors
     * @return array<string, string>
     */
    private function translate(array $errors): array
    {
        $out = [];
        foreach ($errors as $field => $key) {
            $out[$field] = __($key, ['attribute' => $field, 'min' => 8]);
        }

        return $out;
    }

    private function userId(): ?int
    {
        /** @var AuthService $auth */
        $auth = $this->container->get(AuthService::class);

        return $auth->id();
    }

    private function users(): UserRepository
    {
        /** @var UserRepository $repository */
        $repository = $this->container->get(UserRepository::class);

        return $repository;
    }

    private function roles(): RoleRepository
    {
        /** @var RoleRepository $repository */
        $repository = $this->container->get(RoleRepository::class);

        return $repository;
    }

    private function userService(): UserService
    {
        /** @var UserService $service */
        $service = $this->container->get(UserService::class);

        return $service;
    }

    private function log(): ActivityLogService
    {
        /** @var ActivityLogService $log */
        $log = $this->container->get(ActivityLogService::class);

        return $log;
    }
}
