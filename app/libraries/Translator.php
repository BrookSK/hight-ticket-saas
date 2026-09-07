<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Translation system.
 *
 * No user-facing text may be hard-coded. Every label, placeholder, validation
 * and error message is resolved through this translator, keyed by dot-notation
 * (e.g. "auth.login.title"). Language files live in app/lang/{locale}.
 */
final class Translator
{
    private string $locale;

    private string $fallbackLocale;

    private string $basePath;

    /** @var array<string, array<string, mixed>> Loaded groups per locale. */
    private array $loaded = [];

    public function __construct(string $locale, string $fallbackLocale, ?string $basePath = null)
    {
        $this->locale = $locale;
        $this->fallbackLocale = $fallbackLocale;
        $this->basePath = $basePath ?? dirname(__DIR__) . '/lang';
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    /**
     * Translate a key with optional placeholder replacements.
     *
     * @param array<string, string|int|float> $replacements
     */
    public function get(string $key, array $replacements = []): string
    {
        $value = $this->lookup($key, $this->locale);

        if ($value === null && $this->fallbackLocale !== $this->locale) {
            $value = $this->lookup($key, $this->fallbackLocale);
        }

        // Fall back to the key itself so missing translations are visible.
        $text = $value ?? $key;

        foreach ($replacements as $placeholder => $replacement) {
            $text = str_replace(':' . $placeholder, (string) $replacement, $text);
        }

        return $text;
    }

    /**
     * Resolve a dot-notation key against a locale's group files.
     */
    private function lookup(string $key, string $locale): ?string
    {
        [$group, $item] = array_pad(explode('.', $key, 2), 2, null);

        if ($group === null || $item === null) {
            return null;
        }

        $translations = $this->loadGroup($locale, $group);

        $segments = explode('.', $item);
        $current = $translations;
        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return is_string($current) ? $current : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadGroup(string $locale, string $group): array
    {
        $cacheKey = $locale . ':' . $group;
        if (isset($this->loaded[$cacheKey])) {
            return $this->loaded[$cacheKey];
        }

        $file = $this->basePath . '/' . $locale . '/' . $group . '.php';
        $data = is_file($file) ? require $file : [];

        if (!is_array($data)) {
            $data = [];
        }

        $this->loaded[$cacheKey] = $data;

        return $data;
    }
}
