<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\ActivityLogRepository;

/**
 * Admin viewing of the action audit log. Controls flow only.
 */
final class LogController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $this->render('admin.logs.index', [
            'title'      => __('admin.logs.title'),
            'activePath' => '/app/logs',
            'logs'       => $this->logs()->recent(100),
        ], 'admin');
    }

    private function logs(): ActivityLogRepository
    {
        /** @var ActivityLogRepository $repository */
        $repository = $this->container->get(ActivityLogRepository::class);

        return $repository;
    }
}
