<?php

declare(strict_types=1);

namespace App\Libraries\Audit;

/**
 * Minimal robots.txt parser.
 *
 * Parses disallow/allow rules for a given user-agent and extracts sitemap
 * references. Used to respect crawling rules and to discover sitemaps.
 */
final class RobotsTxt
{
    /** @var list<string> */
    private array $disallow = [];

    /** @var list<string> */
    private array $allow = [];

    /** @var list<string> */
    private array $sitemaps = [];

    private bool $found = false;

    public function __construct(private readonly string $userAgentToken = 'lrvwebauditbot')
    {
    }

    public function parse(string $content): void
    {
        $this->found = true;
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $appliesToUs = false;
        $appliesToAll = false;

        foreach ($lines as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line) ?? '');
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }

            [$field, $value] = array_map('trim', explode(':', $line, 2));
            $field = strtolower($field);

            if ($field === 'user-agent') {
                $ua = strtolower($value);
                $appliesToAll = $ua === '*';
                $appliesToUs = str_contains($this->userAgentToken, $ua) || $ua === '*';
                continue;
            }

            if ($field === 'sitemap') {
                $this->sitemaps[] = $value;
                continue;
            }

            if (!$appliesToUs && !$appliesToAll) {
                continue;
            }

            if ($field === 'disallow' && $value !== '') {
                $this->disallow[] = $value;
            } elseif ($field === 'allow' && $value !== '') {
                $this->allow[] = $value;
            }
        }
    }

    /**
     * Whether crawling the given path is allowed. Allow rules win over Disallow
     * when more specific (longer match).
     */
    public function isAllowed(string $path): bool
    {
        $disallowMatch = $this->longestMatch($path, $this->disallow);
        $allowMatch = $this->longestMatch($path, $this->allow);

        if ($disallowMatch === 0) {
            return true;
        }

        return $allowMatch >= $disallowMatch;
    }

    /**
     * @return list<string>
     */
    public function sitemaps(): array
    {
        return $this->sitemaps;
    }

    public function wasFound(): bool
    {
        return $this->found;
    }

    /**
     * @param list<string> $rules
     */
    private function longestMatch(string $path, array $rules): int
    {
        $best = 0;
        foreach ($rules as $rule) {
            $prefix = rtrim($rule, '*');
            if ($prefix === '' || str_starts_with($path, $prefix)) {
                $best = max($best, strlen($prefix));
            }
        }

        return $best;
    }
}
