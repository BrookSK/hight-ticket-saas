<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Repositories\PlanRepository;

/**
 * Plan business logic.
 *
 * Manages commercial plans (create/update/delete) and exposes the active plans
 * for public display. Prices and content are stored in the database and never
 * hardcoded in views.
 */
final class PlanService extends Service
{
    public const STATUSES = ['active', 'inactive'];

    /**
     * Active plans for public pages, with decoded feature/limitation lists.
     *
     * @return array<int, array<string, mixed>>
     */
    public function publicPlans(): array
    {
        return array_map(
            [$this, 'decodeLists'],
            $this->repository()->allActiveOrdered()
        );
    }

    /**
     * All plans for admin listing.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allForAdmin(): array
    {
        return array_map([$this, 'decodeLists'], $this->repository()->allOrdered());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $plan = $this->repository()->findById($id);

        return $plan === null ? null : $this->decodeLists($plan);
    }

    /**
     * Create a plan from admin input.
     *
     * @param array<string, mixed> $input
     * @return array{ok:bool, errors?:array<string,string>, id?:int}
     */
    public function create(array $input): array
    {
        $errors = $this->validate($input);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $data = $this->mapInput($input);
        if ($this->repository()->slugExists($data['slug'])) {
            return ['ok' => false, 'errors' => ['slug' => 'validation.unique']];
        }

        $id = $this->repository()->create($data);

        return ['ok' => true, 'id' => $id];
    }

    /**
     * Update a plan from admin input.
     *
     * @param array<string, mixed> $input
     * @return array{ok:bool, errors?:array<string,string>}
     */
    public function update(int $id, array $input): array
    {
        if ($this->repository()->findById($id) === null) {
            return ['ok' => false, 'errors' => ['id' => 'errors.not_found']];
        }

        $errors = $this->validate($input);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $data = $this->mapInput($input);
        if ($this->repository()->slugExists($data['slug'], $id)) {
            return ['ok' => false, 'errors' => ['slug' => 'validation.unique']];
        }

        $this->repository()->update($id, $data);

        return ['ok' => true];
    }

    public function delete(int $id): bool
    {
        if ($this->repository()->findById($id) === null) {
            return false;
        }

        $this->repository()->softDelete($id);

        return true;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function validate(array $input): array
    {
        $errors = [];

        if (trim((string) ($input['name'] ?? '')) === '') {
            $errors['name'] = 'validation.required';
        }
        if (trim((string) ($input['slug'] ?? '')) === '') {
            $errors['slug'] = 'validation.required';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function mapInput(array $input): array
    {
        $features = $this->linesToJson((string) ($input['features'] ?? ''));
        $limitations = $this->linesToJson((string) ($input['limitations'] ?? ''));

        return [
            'name'          => trim((string) $input['name']),
            'slug'          => $this->slugify((string) $input['slug']),
            'description'   => $this->nullableTrim($input['description'] ?? null),
            'price_monthly' => $this->nullablePrice($input['price_monthly'] ?? null),
            'price_yearly'  => $this->nullablePrice($input['price_yearly'] ?? null),
            'currency'      => strtoupper(trim((string) ($input['currency'] ?? 'BRL'))) ?: 'BRL',
            'cta_label'     => $this->nullableTrim($input['cta_label'] ?? null),
            'cta_url'       => $this->nullableTrim($input['cta_url'] ?? null),
            'features'      => $features,
            'limitations'   => $limitations,
            'is_featured'   => !empty($input['is_featured']) ? 1 : 0,
            'sort_order'    => (int) ($input['sort_order'] ?? 0),
            'status'        => in_array($input['status'] ?? '', self::STATUSES, true)
                ? (string) $input['status']
                : 'active',
        ];
    }

    /**
     * Decode features/limitations JSON columns into arrays for the views.
     *
     * @param array<string, mixed> $plan
     * @return array<string, mixed>
     */
    private function decodeLists(array $plan): array
    {
        $plan['features'] = $this->jsonToArray($plan['features'] ?? null);
        $plan['limitations'] = $this->jsonToArray($plan['limitations'] ?? null);

        return $plan;
    }

    /**
     * @return list<string>
     */
    private function jsonToArray(mixed $value): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }

    private function linesToJson(string $text): ?string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text) ?: [])));

        return $lines === [] ? null : json_encode($lines, JSON_UNESCAPED_UNICODE);
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    private function nullablePrice(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', (string) $value);

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function repository(): PlanRepository
    {
        /** @var PlanRepository $repository */
        $repository = $this->container->get(PlanRepository::class);

        return $repository;
    }
}
