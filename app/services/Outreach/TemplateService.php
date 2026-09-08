<?php

declare(strict_types=1);

namespace App\Services\Outreach;

use App\Core\Service;
use App\Repositories\OutreachTemplateRepository;
use App\Services\AccessContext;

/**
 * CRUD for outreach templates, with variable validation.
 *
 * A template can only reference known variables; creating/updating with an
 * unknown {{variable}} is rejected so broken templates never reach production.
 */
final class TemplateService extends Service
{
    public const CHANNELS = ['whatsapp', 'email'];
    public const KINDS = ['first_contact', 'follow_up', 'diagnosis', 'proposal', 'meeting', 'reactivation'];

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool, id?:int, errors?:array<string,string>, invalid?:array<int,string>}
     */
    public function create(array $input): array
    {
        $validation = $this->validate($input);
        if ($validation !== null) {
            return $validation;
        }

        $ctx = $this->context();
        $channel = in_array($input['channel'] ?? '', self::CHANNELS, true) ? (string) $input['channel'] : 'whatsapp';
        $id = $this->repository()->create([
            'owner_type' => $ctx->ownerType(),
            'owner_id'   => $ctx->ownerId(),
            'name'       => trim((string) $input['name']),
            'channel'    => $channel,
            'kind'       => in_array($input['kind'] ?? '', self::KINDS, true) ? (string) $input['kind'] : 'first_contact',
            'subject'    => $channel === 'email' ? $this->nt($input['subject'] ?? null) : null,
            'body'       => (string) $input['body'],
            'is_active'  => empty($input['is_active']) ? 0 : 1,
            'created_by' => $ctx->userId(),
        ]);

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool, errors?:array<string,string>, invalid?:array<int,string>}
     */
    public function update(int $id, array $input): array
    {
        if ($this->find($id) === null) {
            return ['ok' => false, 'errors' => ['id' => 'errors.not_found']];
        }
        $validation = $this->validate($input);
        if ($validation !== null) {
            return $validation;
        }

        $channel = in_array($input['channel'] ?? '', self::CHANNELS, true) ? (string) $input['channel'] : 'whatsapp';
        $this->repository()->update($id, [
            'name'      => trim((string) $input['name']),
            'channel'   => $channel,
            'kind'      => in_array($input['kind'] ?? '', self::KINDS, true) ? (string) $input['kind'] : 'first_contact',
            'subject'   => $channel === 'email' ? $this->nt($input['subject'] ?? null) : null,
            'body'      => (string) $input['body'],
            'is_active' => empty($input['is_active']) ? 0 : 1,
        ]);

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
     * @return array<int, array<string, mixed>>
     */
    public function listForContext(?string $channel = null): array
    {
        $ctx = $this->context();

        return $this->repository()->listForContext($ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll(), $channel);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $ctx = $this->context();

        return $this->repository()->findForContext($id, $ctx->ownerType(), $ctx->ownerId(), $ctx->canSeeAll());
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool, errors?:array<string,string>, invalid?:array<int,string>}|null
     */
    private function validate(array $input): ?array
    {
        if (trim((string) ($input['name'] ?? '')) === '') {
            return ['ok' => false, 'errors' => ['name' => 'validation.required']];
        }
        $body = (string) ($input['body'] ?? '');
        if (trim($body) === '') {
            return ['ok' => false, 'errors' => ['body' => 'validation.required']];
        }
        $invalid = $this->renderer()->invalidVariables($body, $this->outreach()->availableVariables());
        if ($invalid !== []) {
            return ['ok' => false, 'errors' => ['body' => 'outreach.errors.unknown_variable'], 'invalid' => $invalid];
        }

        return null;
    }

    private function nt(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $t = trim((string) $v);

        return $t === '' ? null : $t;
    }

    private function repository(): OutreachTemplateRepository
    {
        /** @var OutreachTemplateRepository $r */
        $r = $this->container->get(OutreachTemplateRepository::class);

        return $r;
    }

    private function renderer(): TemplateRenderer
    {
        /** @var TemplateRenderer $s */
        $s = $this->container->get(TemplateRenderer::class);

        return $s;
    }

    private function outreach(): OutreachService
    {
        /** @var OutreachService $s */
        $s = $this->container->get(OutreachService::class);

        return $s;
    }

    private function context(): AccessContext
    {
        /** @var AccessContext $c */
        $c = $this->container->get(AccessContext::class);

        return $c;
    }
}
