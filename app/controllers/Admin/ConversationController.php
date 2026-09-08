<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\ConversationRepository;
use App\Repositories\OutreachMessageRepository;
use App\Services\AccessContext;
use App\Services\ActivityLogService;
use App\Services\Outreach\ConversationService;

/**
 * Conversations inbox and thread view. Controls flow only.
 */
final class ConversationController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $filters = [
            'status'      => (string) $request->query('status', ''),
            'needs_human' => $request->query('needs_human', '') !== '' ? 1 : null,
        ];

        $this->render('admin.outreach.conversations.index', [
            'title'         => __('outreach.conversations.title'),
            'activePath'    => '/app/outreach/conversations',
            'conversations' => $this->service()->listForContext($filters),
            'filters'       => $filters,
        ], 'app');
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): void
    {
        $ctx = $this->context();
        $id = (int) ($params['id'] ?? 0);
        $thread = $this->threads()->findForContext($id, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
        if ($thread === null) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        $messages = $thread['lead_id'] !== null ? $this->messages()->forLead((int) $thread['lead_id']) : [];

        $this->render('admin.outreach.conversations.show', [
            'title'      => __('outreach.conversations.title'),
            'activePath' => '/app/outreach/conversations',
            'thread'     => $thread,
            'messages'   => $messages,
        ], 'app');
    }

    /**
     * @param array<string, string> $params
     */
    public function close(Request $request, array $params = []): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->service()->close($id)) {
            $this->log()->record('conversation_closed', $this->context()->userId(), ['object_type' => 'conversation_thread', 'object_id' => $id]);
            $this->session()->flash('status', __('outreach.conversations.closed'));
        }
        $this->redirect('/app/outreach/conversations');
    }

    private function threads(): ConversationRepository
    {
        /** @var ConversationRepository $r */
        $r = $this->container->get(ConversationRepository::class);

        return $r;
    }

    private function service(): ConversationService
    {
        /** @var ConversationService $s */
        $s = $this->container->get(ConversationService::class);

        return $s;
    }

    private function messages(): OutreachMessageRepository
    {
        /** @var OutreachMessageRepository $r */
        $r = $this->container->get(OutreachMessageRepository::class);

        return $r;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }

    private function log(): ActivityLogService
    {
        /** @var ActivityLogService $l */
        $l = $this->container->get(ActivityLogService::class);

        return $l;
    }
}

