<?php

declare(strict_types=1);

namespace App\Services\Outreach;

use App\Core\Service;

/**
 * Renders message templates with {{variable}} placeholders.
 *
 * - Variables are named tokens: {{first_name}}, {{company}}, {{report_link}}...
 * - Rendering fails (UnknownTemplateVariableException) if the template uses a
 *   variable that is not supplied — never send a broken/placeholder message.
 * - Values are used as-is (plain text). Output escaping is the responsibility
 *   of the channel/view layer (e-mail HTML vs WhatsApp text).
 */
final class TemplateRenderer extends Service
{
    /**
     * Extract the variable names referenced by a template body.
     *
     * @return array<int, string>
     */
    public function variablesIn(string $body): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $body, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * Render a template. Throws when the template references variables that are
     * not present in $vars.
     *
     * @param array<string, string|int|float|null> $vars
     */
    public function render(string $body, array $vars): string
    {
        $referenced = $this->variablesIn($body);
        $unknown = [];
        foreach ($referenced as $name) {
            if (!array_key_exists($name, $vars)) {
                $unknown[] = $name;
            }
        }
        if ($unknown !== []) {
            throw new UnknownTemplateVariableException($unknown);
        }

        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            static fn (array $m): string => (string) ($vars[$m[1]] ?? ''),
            $body
        ) ?? $body;
    }

    /**
     * Validate a template body against the known/available variable names.
     * Returns the list of variables that are NOT available (empty = valid).
     *
     * @param array<int, string> $available
     * @return array<int, string>
     */
    public function invalidVariables(string $body, array $available): array
    {
        $referenced = $this->variablesIn($body);

        return array_values(array_diff($referenced, $available));
    }
}
