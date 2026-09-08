<?php

declare(strict_types=1);

namespace App\Services\Prospecting;

use App\Core\Service;
use App\Repositories\CampaignRepository;
use App\Repositories\ProspectingJobRepository;
use App\Services\AccessContext;
use App\Services\ConfigService;

/**
 * Campaign lifecycle business logic.
 *
 * Creates campaigns (respecting active-campaign limits), enqueues the initial
 * discovery job (async — never runs discovery in the web request), and handles
 * pause/cancel/delete. Owner-scoped.
 */
final class CampaignService extends Service
{
    public const RESULT_OK = 'ok';
    public const RESULT_LIMIT = 'limit_reached';
    public const RESULT_INVALID = 'invalid';

    /**
     * @param array<string, mixed> $input
     * @return array{result:string, id?:int, errors?:array<string,string>}
     */
    public function create(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            return ['result' => self::RESULT_INVALID, 'errors' => ['name' => 'validation.required']];
        }

        $ctx = $this->context();
        $ownerId = $ctx->ownerId();

        // Enforce active-campaign limit per owner (anti-abuse / plan limit).
        if ($ownerId !== null && !$ctx->canSeeAll()) {
            $maxActive = (int) ($this->config()->get('prospecting_max_active_campaigns', '3') ?? 3);
            if ($this->campaigns()->countActiveForOwner($ctx->ownerType(), $ownerId) >= $maxActive) {
                return ['result' => self::RESULT_LIMIT];
            }
        }

        $maxResultsCap = (int) ($this->config()->get('prospecting_max_results_per_campaign', '200') ?? 200);
        $maxResults = max(1, min($maxResultsCap, (int) ($input['max_results'] ?? 100)));

        $id = $this->campaigns()->create([
            'owner_type'            => $ctx->ownerType(),
            'owner_id'              => $ownerId,
            'created_by'            => $ctx->userId(),
            'name'                  => $name,
            'provider'              => (string) ($input['provider'] ?? 'imported_list'),
            'status'                => 'draft',
            'segment'               => $this->nt($input['segment'] ?? null),
            'keywords'              => $this->nt($input['keywords'] ?? null),
            'city'                  => $this->nt($input['city'] ?? null),
            'state'                 => $this->nt($input['state'] ?? null),
            'country'               => $this->nt($input['country'] ?? null),
            'website_filter'        => in_array($input['website_filter'] ?? '', ['any', 'with', 'without'], true) ? (string) $input['website_filter'] : 'any',
            'technology_filter'     => $this->nt($input['technology_filter'] ?? null),
            'min_opportunity_score' => max(0, min(100, (int) ($input['min_opportunity_score'] ?? 0))),
            'target_service'        => $this->nt($input['target_service'] ?? null),
            'max_results'           => $maxResults,
            'max_audits'            => max(0, (int) ($input['max_audits'] ?? 50)),
            'auto_audit'            => !empty($input['auto_audit']) ? 1 : 0,
            'input_payload'         => $this->nt($input['input_payload'] ?? null),
        ]);

        return ['result' => self::RESULT_OK, 'id' => $id];
    }

    /**
     * Start a campaign: enqueue the discovery job. Returns success.
     */
    public function run(int $id): bool
    {
        $campaign = $this->find($id);
        if ($campaign === null || in_array($campaign['status'], ['processing'], true)) {
            return false;
        }

        $this->campaigns()->markStarted($id);
        $this->campaigns()->setProgress($id, 2, 'queued');
        $this->jobs()->enqueue($id, 'discovery', null, ['campaign_id' => $id]);

        return true;
    }

    public function pause(int $id): bool
    {
        $campaign = $this->find($id);
        if ($campaign === null) {
            return false;
        }
        $this->campaigns()->updateStatus($id, 'paused');
        $this->jobs()->cancelQueuedForCampaign($id);

        return true;
    }

    public function cancel(int $id): bool
    {
        $campaign = $this->find($id);
        if ($campaign === null) {
            return false;
        }
        $this->campaigns()->updateStatus($id, 'cancelled');
        $this->jobs()->cancelQueuedForCampaign($id);

        return true;
    }

    public function delete(int $id): bool
    {
        if ($this->find($id) === null) {
            return false;
        }
        $this->campaigns()->softDelete($id);

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $ctx = $this->context();

        return $this->campaigns()->findForContext($id, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
    }

    private function nt(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $t = trim((string) $v);

        return $t === '' ? null : $t;
    }

    private function campaigns(): CampaignRepository
    {
        /** @var CampaignRepository $r */
        $r = $this->container->get(CampaignRepository::class);

        return $r;
    }

    private function jobs(): ProspectingJobRepository
    {
        /** @var ProspectingJobRepository $r */
        $r = $this->container->get(ProspectingJobRepository::class);

        return $r;
    }

    private function config(): ConfigService
    {
        /** @var ConfigService $c */
        $c = $this->container->get(ConfigService::class);

        return $c;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
