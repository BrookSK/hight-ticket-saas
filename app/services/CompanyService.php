<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Repositories\CompanyRepository;

/**
 * Company business logic.
 *
 * Normalizes domains for identity/deduplication, checks for possible
 * duplicates (without hard-blocking), and manages status/archival. Owner-scoped
 * through AccessContext.
 */
final class CompanyService extends Service
{
    public const STATUSES = ['active', 'inactive', 'archived'];

    /**
     * Normalize a website/URL to a canonical domain (host without www/scheme).
     */
    public function normalizeDomain(?string $website): ?string
    {
        if ($website === null) {
            return null;
        }
        $website = trim($website);
        if ($website === '') {
            return null;
        }
        if (!preg_match('#^https?://#i', $website)) {
            $website = 'http://' . $website;
        }
        $host = parse_url($website, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }
        $host = strtolower($host);

        return preg_replace('/^www\./', '', $host);
    }

    /**
     * Possible duplicates for the given identifying data (owner-scoped).
     *
     * @return array<int, array<string, mixed>>
     */
    public function possibleDuplicates(?string $website, ?string $cnpj, ?string $email, ?string $phone): array
    {
        $ctx = $this->context();
        $ownerId = $ctx->ownerId();
        if ($ownerId === null) {
            return [];
        }

        return $this->repository()->findPossibleDuplicates(
            $ctx->ownerType(),
            $ownerId,
            $this->normalizeDomain($website),
            $this->digits($cnpj),
            $email !== null ? strtolower(trim($email)) : null,
            $phone
        );
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool, id?:int, errors?:array<string,string>}
     */
    public function create(array $input): array
    {
        $tradeName = trim((string) ($input['trade_name'] ?? ''));
        if ($tradeName === '') {
            return ['ok' => false, 'errors' => ['trade_name' => 'validation.required']];
        }

        $ctx = $this->context();
        $id = $this->repository()->create([
            'owner_type'  => $ctx->ownerType(),
            'owner_id'    => $ctx->ownerId(),
            'created_by'  => $ctx->userId(),
            'legal_name'  => $this->nt($input['legal_name'] ?? null),
            'trade_name'  => $tradeName,
            'cnpj'        => $this->digits($input['cnpj'] ?? null),
            'website'     => $this->nt($input['website'] ?? null),
            'domain'      => $this->normalizeDomain($input['website'] ?? null),
            'phone'       => $this->nt($input['phone'] ?? null),
            'whatsapp'    => $this->nt($input['whatsapp'] ?? null),
            'email'       => $this->nt($input['email'] ?? null),
            'address'     => $this->nt($input['address'] ?? null),
            'city'        => $this->nt($input['city'] ?? null),
            'state'       => $this->nt($input['state'] ?? null),
            'country'     => $this->nt($input['country'] ?? null),
            'zip_code'    => $this->nt($input['zip_code'] ?? null),
            'segment'     => $this->nt($input['segment'] ?? null),
            'description' => $this->nt($input['description'] ?? null),
            'instagram'   => $this->nt($input['instagram'] ?? null),
            'facebook'    => $this->nt($input['facebook'] ?? null),
            'linkedin'    => $this->nt($input['linkedin'] ?? null),
            'youtube'     => $this->nt($input['youtube'] ?? null),
            'status'      => 'active',
            'source'      => $this->nt($input['source'] ?? null),
        ]);

        return ['ok' => true, 'id' => $id];
    }

    /**
     * Find or create a company by normalized domain (used by "transform to lead").
     */
    public function findOrCreateByDomain(string $website, string $tradeName): int
    {
        $ctx = $this->context();
        $ownerId = $ctx->ownerId();
        $domain = $this->normalizeDomain($website);

        if ($domain !== null && $ownerId !== null) {
            $dupes = $this->repository()->findPossibleDuplicates($ctx->ownerType(), $ownerId, $domain, null, null, null);
            if ($dupes !== []) {
                return (int) $dupes[0]['id'];
            }
        }

        $result = $this->create(['trade_name' => $tradeName, 'website' => $website, 'source' => 'audit']);

        return (int) ($result['id'] ?? 0);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool, errors?:array<string,string>}
     */
    public function update(int $id, array $input): array
    {
        if ($this->find($id) === null) {
            return ['ok' => false, 'errors' => ['id' => 'errors.not_found']];
        }
        $tradeName = trim((string) ($input['trade_name'] ?? ''));
        if ($tradeName === '') {
            return ['ok' => false, 'errors' => ['trade_name' => 'validation.required']];
        }

        $this->repository()->update($id, [
            'legal_name'  => $this->nt($input['legal_name'] ?? null),
            'trade_name'  => $tradeName,
            'cnpj'        => $this->digits($input['cnpj'] ?? null),
            'website'     => $this->nt($input['website'] ?? null),
            'domain'      => $this->normalizeDomain($input['website'] ?? null),
            'phone'       => $this->nt($input['phone'] ?? null),
            'whatsapp'    => $this->nt($input['whatsapp'] ?? null),
            'email'       => $this->nt($input['email'] ?? null),
            'address'     => $this->nt($input['address'] ?? null),
            'city'        => $this->nt($input['city'] ?? null),
            'state'       => $this->nt($input['state'] ?? null),
            'country'     => $this->nt($input['country'] ?? null),
            'zip_code'    => $this->nt($input['zip_code'] ?? null),
            'segment'     => $this->nt($input['segment'] ?? null),
            'description' => $this->nt($input['description'] ?? null),
            'instagram'   => $this->nt($input['instagram'] ?? null),
            'facebook'    => $this->nt($input['facebook'] ?? null),
            'linkedin'    => $this->nt($input['linkedin'] ?? null),
            'youtube'     => $this->nt($input['youtube'] ?? null),
            'status'      => in_array($input['status'] ?? '', self::STATUSES, true) ? (string) $input['status'] : 'active',
        ]);

        return ['ok' => true];
    }

    public function archive(int $id): bool
    {
        if ($this->find($id) === null) {
            return false;
        }
        $this->repository()->updateStatus($id, 'archived');

        return true;
    }

    public function restore(int $id): bool
    {
        if ($this->find($id) === null) {
            return false;
        }
        $this->repository()->updateStatus($id, 'active');

        return true;
    }

    public function delete(int $id): bool
    {
        if ($this->find($id) === null) {
            return false;
        }
        $this->repository()->softDelete($id);

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $ctx = $this->context();

        return $this->repository()->findForContext($id, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
    }

    private function digits(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $d = preg_replace('/\D+/', '', (string) $v) ?? '';

        return $d === '' ? null : $d;
    }

    private function nt(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $t = trim((string) $v);

        return $t === '' ? null : $t;
    }

    private function repository(): CompanyRepository
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
