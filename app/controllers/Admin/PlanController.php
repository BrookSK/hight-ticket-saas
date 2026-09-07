<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\PlanService;

/**
 * Admin management of commercial plans (no hardcoded prices).
 *
 * Controls flow only; PlanService holds the rules.
 */
final class PlanController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $this->render('admin.plans.index', [
            'title'      => __('admin.plans.title'),
            'activePath' => '/app/plans',
            'plans'      => $this->plans()->allForAdmin(),
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function create(Request $request, array $params = []): void
    {
        $this->render('admin.plans.form', [
            'title'      => __('admin.plans.new'),
            'activePath' => '/app/plans',
            'plan'       => null,
            'errors'     => [],
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): void
    {
        $result = $this->plans()->create($request->all());
        if (!($result['ok'] ?? false)) {
            $this->render('admin.plans.form', [
                'title'      => __('admin.plans.new'),
                'activePath' => '/app/plans',
                'plan'       => $request->all(),
                'errors'     => $this->translate($result['errors'] ?? []),
            ], 'admin', 422);

            return;
        }

        $this->log()->record('plan_created', $this->userId(), ['object_type' => 'plan', 'object_id' => $result['id'] ?? null]);
        $this->session()->flash('status', __('admin.plans.created'));
        $this->redirect('/app/plans');
    }

    /**
     * @param array<string, string> $params
     */
    public function edit(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $plan = $this->plans()->find($id);
        if ($plan === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $this->render('admin.plans.form', [
            'title'      => __('admin.plans.edit'),
            'activePath' => '/app/plans',
            'plan'       => $plan,
            'errors'     => [],
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $result = $this->plans()->update($id, $request->all());
        if (!($result['ok'] ?? false)) {
            $plan = $request->all();
            $plan['id'] = $id;
            $this->render('admin.plans.form', [
                'title'      => __('admin.plans.edit'),
                'activePath' => '/app/plans',
                'plan'       => $plan,
                'errors'     => $this->translate($result['errors'] ?? []),
            ], 'admin', 422);

            return;
        }

        $this->log()->record('plan_updated', $this->userId(), ['object_type' => 'plan', 'object_id' => $id]);
        $this->session()->flash('status', __('admin.plans.updated'));
        $this->redirect('/app/plans');
    }

    /**
     * @param array<string, string> $params
     */
    public function delete(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->plans()->delete($id)) {
            $this->log()->record('plan_deleted', $this->userId(), ['object_type' => 'plan', 'object_id' => $id]);
            $this->session()->flash('status', __('admin.plans.deleted'));
        }

        $this->redirect('/app/plans');
    }

    /**
     * @param array<string, string> $errors
     * @return array<string, string>
     */
    private function translate(array $errors): array
    {
        $out = [];
        foreach ($errors as $field => $key) {
            $out[$field] = __($key, ['attribute' => $field]);
        }

        return $out;
    }

    private function userId(): ?int
    {
        /** @var AuthService $auth */
        $auth = $this->container->get(AuthService::class);

        return $auth->id();
    }

    private function plans(): PlanService
    {
        /** @var PlanService $service */
        $service = $this->container->get(PlanService::class);

        return $service;
    }

    private function log(): ActivityLogService
    {
        /** @var ActivityLogService $log */
        $log = $this->container->get(ActivityLogService::class);

        return $log;
    }
}
