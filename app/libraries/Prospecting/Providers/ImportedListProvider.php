<?php

declare(strict_types=1);

namespace App\Libraries\Prospecting\Providers;

use App\Libraries\Prospecting\DiscoveryProviderInterface;
use App\Libraries\Prospecting\DiscoveryResultDto;

/**
 * Imported-list discovery provider.
 *
 * The user supplies the list of companies (name; optional website, phone,
 * city). This is a real, fully-functional source that requires no third-party
 * credentials and performs no scraping — it simply parses what the user pasted.
 *
 * Accepted input (one company per line):
 *   Nome da Empresa; https://site.com.br; (17) 99999-0000; Cidade
 * Fields after the name are optional and separated by ";".
 */
final class ImportedListProvider implements DiscoveryProviderInterface
{
    public function key(): string
    {
        return 'imported_list';
    }

    public function name(): string
    {
        return 'prospecting.provider.imported_list';
    }

    public function isConfigured(): bool
    {
        return true; // no external credentials needed
    }

    /**
     * @return array{max_results:int, delay_ms:int}
     */
    public function limits(): array
    {
        return ['max_results' => 500, 'delay_ms' => 0];
    }

    /**
     * @param array<string, mixed> $criteria
     * @return list<DiscoveryResultDto>
     */
    public function search(array $criteria): array
    {
        $payload = (string) ($criteria['input_payload'] ?? '');
        if (trim($payload) === '') {
            return [];
        }

        $maxResults = (int) ($criteria['max_results'] ?? 200);
        $results = [];
        $lines = preg_split('/\r\n|\r|\n/', $payload) ?: [];

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if ($line === '' || count($results) >= $maxResults) {
                continue;
            }

            $parts = array_map('trim', explode(';', $line));
            $name = $parts[0] ?? '';
            if ($name === '') {
                continue;
            }

            $results[] = new DiscoveryResultDto(
                externalId: 'line:' . ($index + 1),
                name: $name,
                website: $this->cleanField($parts[1] ?? null),
                phone: $this->cleanField($parts[2] ?? null),
                address: $this->cleanField($parts[3] ?? null),
                category: (string) ($criteria['segment'] ?? '') ?: null,
                raw: ['line' => $line]
            );
        }

        return $results;
    }

    private function cleanField(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
