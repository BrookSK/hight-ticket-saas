<?php

declare(strict_types=1);

namespace App\Services\Prospecting;

use App\Core\Service;
use App\Repositories\CampaignRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\DiscoveryResultRepository;
use App\Services\AccessContext;
use App\Services\CompanyService;
use App\Services\ContactService;
use App\Services\LeadService;

/**
 * Converts a reviewed/approved discovery result into commercial records.
 *
 * Finds or creates the company (by normalized domain), optionally creates a
 * contact, creates a lead (recommended service as service_type, campaign as
 * source), and links the audit. Respects deduplication — never creates a
 * duplicate company/lead. Supports batch conversion.
 */
final class ProspectingConversionService extends Service
{
    public const CONVERTED = 'converted';
    public const SKIPPED_DUP = 'skipped_duplicate';
    public const FAILED = 'failed';

    /**
     * Convert a single discovery result. Returns an outcome code.
     */
    public function convert(int $resultId): string
    {
        $ctx = $this->context();
        $result = $this->results()->findForContext($resultId, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
        if ($result === null) {
            return self::FAILED;
        }
        if ((string) $result['status'] === 'converted' || !empty($result['lead_id'])) {
            return self::SKIPPED_DUP;
        }

        // Find or create company.
        $companyId = $this->resolveCompany($result);
        if ($companyId <= 0) {
            return self::FAILED;
        }

        // Optional contact from public data.
        $this->maybeCreateContact($companyId, $result);

        // Create the lead.
        $leadResult = $this->leads()->create([
            'company_id'   => $companyId,
            'title'        => (string) ($result['name'] ?? ''),
            'source'       => 'prospecting',
            'service_type' => $this->serviceLabel($result['recommended_service'] ?? null),
        ]);
        if (!($leadResult['ok'] ?? false)) {
            return self::FAILED;
        }
        $leadId = (int) $leadResult['id'];

        // Link the audit (if any) to the lead.
        if (!empty($result['audit_id'])) {
            $this->leadsRepoLink($leadId, (int) $result['audit_id']);
        }

        $this->results()->linkLead($resultId, $leadId);
        $this->campaigns()->incrementCounter((int) $result['campaign_id'], 'count_converted');

        return self::CONVERTED;
    }

    /**
     * Convert many results, respecting dedupe. Returns per-status counts.
     *
     * @param list<int> $resultIds
     * @return array{converted:int, skipped:int, failed:int}
     */
    public function convertBatch(array $resultIds): array
    {
        $out = ['converted' => 0, 'skipped' => 0, 'failed' => 0];
        foreach ($resultIds as $id) {
            $status = $this->convert((int) $id);
            $out[match ($status) {
                self::CONVERTED => 'converted',
                self::SKIPPED_DUP => 'skipped',
                default => 'failed',
            }]++;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $result
     */
    private function resolveCompany(array $result): int
    {
        if (!empty($result['company_id'])) {
            return (int) $result['company_id'];
        }

        $website = (string) ($result['website'] ?? '');
        $name = (string) ($result['name'] ?? 'Empresa');

        if ($website !== '') {
            return $this->companyService()->findOrCreateByDomain($website, $name);
        }

        // No website: create a company from the collected public data.
        $created = $this->companyService()->create([
            'trade_name' => $name,
            'phone'      => $result['phone'] ?? null,
            'email'      => $result['email'] ?? null,
            'whatsapp'   => $result['whatsapp'] ?? null,
            'city'       => $result['city'] ?? null,
            'state'      => $result['state'] ?? null,
            'segment'    => $result['category'] ?? null,
            'source'     => 'prospecting',
        ]);

        return (int) ($created['id'] ?? 0);
    }

    /**
     * @param array<string, mixed> $result
     */
    private function maybeCreateContact(int $companyId, array $result): void
    {
        $email = $result['email'] ?? null;
        $phone = $result['phone'] ?? null;
        if (($email === null || $email === '') && ($phone === null || $phone === '')) {
            return;
        }

        $this->contacts()->create($companyId, [
            'first_name'      => 'Contato',
            'email'           => $email,
            'phone'           => $phone,
            'whatsapp'        => $result['whatsapp'] ?? null,
            'is_primary'      => 1,
            'source'          => 'prospecting',
            'data_confidence' => 'probable',
        ]);
    }

    private function serviceLabel(mixed $service): ?string
    {
        return $service !== null && $service !== '' ? (string) $service : null;
    }

    private function leadsRepoLink(int $leadId, int $auditId): void
    {
        /** @var \App\Repositories\LeadRepository $repo */
        $repo = $this->container->get(\App\Repositories\LeadRepository::class);
        $repo->linkAudit($leadId, $auditId);
    }

    private function results(): DiscoveryResultRepository
    {
        /** @var DiscoveryResultRepository $r */
        $r = $this->container->get(DiscoveryResultRepository::class);

        return $r;
    }

    private function companyService(): CompanyService
    {
        /** @var CompanyService $s */
        $s = $this->container->get(CompanyService::class);

        return $s;
    }

    private function contacts(): ContactService
    {
        /** @var ContactService $s */
        $s = $this->container->get(ContactService::class);

        return $s;
    }

    private function leads(): LeadService
    {
        /** @var LeadService $s */
        $s = $this->container->get(LeadService::class);

        return $s;
    }

    private function campaigns(): CampaignRepository
    {
        /** @var CampaignRepository $r */
        $r = $this->container->get(CampaignRepository::class);

        return $r;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
