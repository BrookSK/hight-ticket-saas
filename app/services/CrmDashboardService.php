<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Repositories\CompanyRepository;
use App\Repositories\LeadRepository;

/**
 * Commercial dashboard aggregations (owner-scoped).
 *
 * Uses efficient aggregate queries (not row loading) for the indicators.
 */
final class CrmDashboardService extends Service
{
    /**
     * @return array<string, mixed>
     */
    public function metrics(): array
    {
        $ctx = $this->context();
        $ot = $ctx->ownerType();
        $oid = $ctx->ownerId();
        $all = $ctx->canSeeAll();

        $byStatus = $this->leads()->countByStatus($ot, $oid, $all);
        $values = $this->leads()->valueSummary($ot, $oid, $all);

        return [
            'companies'     => $this->companies()->totalForContext($ot, $oid, $all),
            'leads_total'   => array_sum($byStatus),
            'leads_new'     => $byStatus['new'] ?? 0,
            'leads_negotiation' => $byStatus['negotiation'] ?? 0,
            'leads_won'     => $byStatus['won'] ?? 0,
            'leads_lost'    => $byStatus['lost'] ?? 0,
            'by_status'     => $byStatus,
            'value_estimated' => $values['estimated'],
            'value_won'     => $values['won'],
        ];
    }

    private function leads(): LeadRepository
    {
        /** @var LeadRepository $r */
        $r = $this->container->get(LeadRepository::class);

        return $r;
    }

    private function companies(): CompanyRepository
    {
        /** @var CompanyRepository $r */
        $r = $this->container->get(CompanyRepository::class);

        return $r;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
