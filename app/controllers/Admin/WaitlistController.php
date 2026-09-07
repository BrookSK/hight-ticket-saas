<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\WaitlistActivityRepository;
use App\Repositories\WaitlistLeadRepository;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\WaitlistService;

/**
 * Admin management of the waitlist.
 *
 * Controls flow only: listing with filters/search/sort/pagination, viewing a
 * lead with its timeline, changing status, adding notes, deleting and CSV
 * export. Business rules live in WaitlistService; data access in repositories.
 */
final class WaitlistController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        [$filters, $orderBy, $direction, $page] = $this->readQuery($request);

        $repo = $this->leads();
        $total = $repo->count($filters);
        $rows = $repo->paginate($filters, $page, self::PER_PAGE, $orderBy, $direction);
        $pages = (int) ceil($total / self::PER_PAGE);

        $this->render('admin.waitlist.index', [
            'title'      => __('admin.waitlist.title'),
            'activePath' => '/app/waitlist',
            'leads'      => $rows,
            'total'      => $total,
            'page'       => $page,
            'pages'      => max(1, $pages),
            'filters'    => $filters,
            'orderBy'    => $orderBy,
            'direction'  => $direction,
            'statuses'   => WaitlistService::STATUSES,
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $lead = $this->leads()->findById($id);

        if ($lead === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $this->render('admin.waitlist.show', [
            'title'      => $lead['name'],
            'activePath' => '/app/waitlist',
            'lead'       => $lead,
            'activities' => $this->activities()->forLead($id),
            'statuses'   => WaitlistService::STATUSES,
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function updateStatus(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $status = (string) $request->input('status', '');

        if ($this->waitlist()->changeStatus($id, $status, $this->currentUserId())) {
            $this->log()->record('waitlist_status_changed', $this->currentUserId(), [
                'object_type' => 'waitlist_lead',
                'object_id'   => $id,
            ]);
            $this->session()->flash('status', __('admin.waitlist.status_updated'));
        }

        $this->redirect('/app/waitlist/' . $id);
    }

    /**
     * @param array<string, string> $params
     */
    public function updateNotes(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        $notes = (string) $request->input('notes', '');

        if ($this->waitlist()->updateNotes($id, $notes, $this->currentUserId())) {
            $this->log()->record('waitlist_note_added', $this->currentUserId(), [
                'object_type' => 'waitlist_lead',
                'object_id'   => $id,
            ]);
            $this->session()->flash('status', __('admin.waitlist.notes_updated'));
        }

        $this->redirect('/app/waitlist/' . $id);
    }

    /**
     * @param array<string, string> $params
     */
    public function delete(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->leads()->findById($id) !== null) {
            $this->leads()->softDelete($id);
            $this->log()->record('waitlist_deleted', $this->currentUserId(), [
                'object_type' => 'waitlist_lead',
                'object_id'   => $id,
            ]);
            $this->session()->flash('status', __('admin.waitlist.deleted'));
        }

        $this->redirect('/app/waitlist');
    }

    /**
     * Export filtered leads as CSV.
     *
     * @param array<string, string> $params
     */
    public function export(Request $request, array $params = []): void
    {
        [$filters] = $this->readQuery($request);
        $rows = $this->leads()->allForExport($filters);

        $this->log()->record('waitlist_exported', $this->currentUserId(), ['object_type' => 'waitlist_lead']);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="waitlist-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility.
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Nome', 'E-mail', 'Telefone', 'Empresa', 'Sites', 'Serviço', 'Status', 'Origem', 'Cadastro']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['name'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['company'] ?? '',
                $row['sites_quantity'] ?? '',
                $row['main_service'] ?? '',
                $row['status'] ?? '',
                $row['source'] ?? '',
                $row['created_at'] ?? '',
            ]);
        }
        fclose($out);
    }

    /**
     * Parse query params into filters, sort and page.
     *
     * @return array{0:array<string,mixed>,1:string,2:string,3:int}
     */
    private function readQuery(Request $request): array
    {
        $filters = [
            'status'         => (string) $request->query('status', ''),
            'source'         => (string) $request->query('source', ''),
            'main_service'   => (string) $request->query('main_service', ''),
            'sites_quantity' => (string) $request->query('sites_quantity', ''),
            'date_from'      => (string) $request->query('date_from', ''),
            'date_to'        => (string) $request->query('date_to', ''),
            'search'         => (string) $request->query('search', ''),
        ];

        $orderBy = (string) $request->query('order_by', 'created_at');
        $direction = (string) $request->query('direction', 'desc');
        $page = max(1, (int) $request->query('page', '1'));

        return [$filters, $orderBy, $direction, $page];
    }

    private function currentUserId(): ?int
    {
        /** @var AuthService $auth */
        $auth = $this->container->get(AuthService::class);

        return $auth->id();
    }

    private function leads(): WaitlistLeadRepository
    {
        /** @var WaitlistLeadRepository $repository */
        $repository = $this->container->get(WaitlistLeadRepository::class);

        return $repository;
    }

    private function activities(): WaitlistActivityRepository
    {
        /** @var WaitlistActivityRepository $repository */
        $repository = $this->container->get(WaitlistActivityRepository::class);

        return $repository;
    }

    private function waitlist(): WaitlistService
    {
        /** @var WaitlistService $service */
        $service = $this->container->get(WaitlistService::class);

        return $service;
    }

    private function log(): ActivityLogService
    {
        /** @var ActivityLogService $log */
        $log = $this->container->get(ActivityLogService::class);

        return $log;
    }
}
