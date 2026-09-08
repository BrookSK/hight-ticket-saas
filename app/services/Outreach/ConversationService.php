<?php

declare(strict_types=1);

namespace App\Services\Outreach;

use App\Core\Service;
use App\Repositories\ConversationRepository;
use App\Repositories\OutreachMessageRepository;
use App\Services\AccessContext;

/**
 * Manages conversation threads and inbound handling.
 *
 * When a lead replies, the sequence stops (human takes over) and the thread is
 * flagged for the seller. A lightweight intent classifier flags high-intent
 * replies as needing a human immediately — it only reads the message text and
 * never fabricates information.
 */
final class ConversationService extends Service
{
    /** Simple, transparent intent keywords (pt-BR). Extendable/configurable later. */
    private const HIGH_INTENT = ['quero', 'preço', 'preco', 'orçamento', 'orcamento', 'contratar', 'comprar', 'proposta', 'reunião', 'reuniao', 'agendar', 'interessado'];
    private const LOW_INTENT = ['não', 'nao', 'pare', 'parar', 'descadastr', 'sair', 'remover'];

    /**
     * Record an inbound reply from a recipient and update thread + sequence.
     *
     * @return array{ok:bool, thread_id?:int, message_id?:int, intent?:string, needs_human?:bool}
     */
    public function recordInbound(int $leadId, string $channel, string $peerAddress, string $body, ?int $contactId = null): array
    {
        $ctx = $this->context();
        $ownerId = $ctx->ownerId();
        if ($ownerId === null) {
            return ['ok' => false];
        }

        $intent = $this->classifyIntent($body);
        $needsHuman = $intent === 'high';
        $optOut = $intent === 'opt_out';

        // Store the inbound message.
        $messageId = $this->messages()->create([
            'owner_type'      => $ctx->ownerType(),
            'owner_id'        => $ownerId,
            'created_by'      => null,
            'lead_id'         => $leadId,
            'company_id'      => null,
            'contact_id'      => $contactId,
            'campaign_id'     => null,
            'template_id'     => null,
            'report_id'       => null,
            'channel'         => $channel,
            'direction'       => 'inbound',
            'to_address'      => $peerAddress,
            'subject'         => null,
            'body'            => $body,
            'status'          => 'received',
            'scheduled_at'    => null,
            'dedupe_key'      => null,
            'provider_msg_id' => null,
            'provider'        => null,
        ]);

        // Find or create the thread.
        $thread = $this->threads()->findByPeer($ctx->ownerType(), $ownerId, $channel, $peerAddress);
        if ($thread === null) {
            $threadId = $this->threads()->create([
                'owner_type'      => $ctx->ownerType(),
                'owner_id'        => $ownerId,
                'lead_id'         => $leadId,
                'contact_id'      => $contactId,
                'channel'         => $channel,
                'peer_address'    => $peerAddress,
                'status'          => 'replied',
                'intent'          => $intent === 'opt_out' ? 'low' : $intent,
                'last_message_at' => date('Y-m-d H:i:s'),
                'needs_human'     => $needsHuman ? 1 : 0,
            ]);
        } else {
            $threadId = (int) $thread['id'];
            $this->threads()->touch($threadId, 'replied', $intent === 'opt_out' ? 'low' : $intent, $needsHuman);
        }

        // A reply stops the follow-up sequence — the human takes over.
        $this->sequences()->stopForLead($leadId, $optOut ? 'opt_out' : 'replied');

        // Honour opt-out immediately.
        if ($optOut) {
            $this->suppression()->optOut($channel, $peerAddress, 'opt_out');
        }

        return [
            'ok'          => true,
            'thread_id'   => $threadId,
            'message_id'  => $messageId,
            'intent'      => $intent,
            'needs_human' => $needsHuman,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForContext(array $filters = []): array
    {
        $ctx = $this->context();

        return $this->threads()->listForContext($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), $filters);
    }

    public function close(int $threadId): bool
    {
        $ctx = $this->context();
        $thread = $this->threads()->findForContext($threadId, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
        if ($thread === null) {
            return false;
        }
        $this->threads()->setStatus($threadId, 'closed');

        return true;
    }

    /**
     * Classify reply intent from its text. Returns high|medium|low|opt_out.
     */
    public function classifyIntent(string $body): string
    {
        $text = mb_strtolower($body);
        foreach (self::LOW_INTENT as $kw) {
            if (str_contains($text, $kw)) {
                // Distinguish an explicit opt-out from a plain "no".
                if (in_array($kw, ['pare', 'parar', 'descadastr', 'sair', 'remover'], true)) {
                    return 'opt_out';
                }
            }
        }
        foreach (self::HIGH_INTENT as $kw) {
            if (str_contains($text, $kw)) {
                return 'high';
            }
        }

        return 'medium';
    }

    private function threads(): ConversationRepository
    {
        /** @var ConversationRepository $r */
        $r = $this->container->get(ConversationRepository::class);

        return $r;
    }

    private function messages(): OutreachMessageRepository
    {
        /** @var OutreachMessageRepository $r */
        $r = $this->container->get(OutreachMessageRepository::class);

        return $r;
    }

    private function sequences(): SequenceService
    {
        /** @var SequenceService $s */
        $s = $this->container->get(SequenceService::class);

        return $s;
    }

    private function suppression(): SuppressionService
    {
        /** @var SuppressionService $s */
        $s = $this->container->get(SuppressionService::class);

        return $s;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
