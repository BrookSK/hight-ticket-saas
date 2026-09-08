<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Repositories\ContactRepository;

/**
 * Contact business logic (owner-scoped). Handles primary-contact uniqueness.
 */
final class ContactService extends Service
{
    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool, id?:int, errors?:array<string,string>}
     */
    public function create(int $companyId, array $input): array
    {
        $first = trim((string) ($input['first_name'] ?? ''));
        if ($first === '') {
            return ['ok' => false, 'errors' => ['first_name' => 'validation.required']];
        }

        $email = $this->nt($input['email'] ?? null);
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'errors' => ['email' => 'validation.email']];
        }

        $ctx = $this->context();
        $isPrimary = !empty($input['is_primary']);
        if ($isPrimary) {
            $this->repository()->clearPrimary($companyId);
        }

        $id = $this->repository()->create([
            'owner_type'      => $ctx->ownerType(),
            'owner_id'        => $ctx->ownerId(),
            'company_id'      => $companyId,
            'first_name'      => $first,
            'last_name'       => $this->nt($input['last_name'] ?? null),
            'role_title'      => $this->nt($input['role_title'] ?? null),
            'email'           => $email,
            'phone'           => $this->nt($input['phone'] ?? null),
            'whatsapp'        => $this->nt($input['whatsapp'] ?? null),
            'linkedin'        => $this->nt($input['linkedin'] ?? null),
            'notes'           => $this->nt($input['notes'] ?? null),
            'is_primary'      => $isPrimary ? 1 : 0,
            'status'          => 'active',
            'source'          => $this->nt($input['source'] ?? null) ?? 'user',
            'data_confidence' => (string) ($input['data_confidence'] ?? 'confirmed'),
            'consent'         => !empty($input['consent']) ? 1 : 0,
        ]);

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool, errors?:array<string,string>}
     */
    public function update(int $id, array $input): array
    {
        $contact = $this->find($id);
        if ($contact === null) {
            return ['ok' => false, 'errors' => ['id' => 'errors.not_found']];
        }
        $first = trim((string) ($input['first_name'] ?? ''));
        if ($first === '') {
            return ['ok' => false, 'errors' => ['first_name' => 'validation.required']];
        }

        $this->repository()->update($id, [
            'first_name' => $first,
            'last_name'  => $this->nt($input['last_name'] ?? null),
            'role_title' => $this->nt($input['role_title'] ?? null),
            'email'      => $this->nt($input['email'] ?? null),
            'phone'      => $this->nt($input['phone'] ?? null),
            'whatsapp'   => $this->nt($input['whatsapp'] ?? null),
            'linkedin'   => $this->nt($input['linkedin'] ?? null),
            'notes'      => $this->nt($input['notes'] ?? null),
            'status'     => (string) ($input['status'] ?? 'active'),
        ]);

        if (!empty($input['is_primary'])) {
            $this->repository()->clearPrimary((int) $contact['company_id']);
            $this->repository()->setPrimary($id);
        }

        return ['ok' => true];
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

    private function nt(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $t = trim((string) $v);

        return $t === '' ? null : $t;
    }

    private function repository(): ContactRepository
    {
        /** @var ContactRepository $r */
        $r = $this->container->get(ContactRepository::class);

        return $r;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
