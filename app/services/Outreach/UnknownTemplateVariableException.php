<?php

declare(strict_types=1);

namespace App\Services\Outreach;

use RuntimeException;

/**
 * Raised when a template references a variable that is not provided.
 *
 * Per the spec, rendering must fail loudly on unknown variables instead of
 * silently sending a broken message.
 */
final class UnknownTemplateVariableException extends RuntimeException
{
    /** @var array<int, string> */
    public array $unknown;

    /**
     * @param array<int, string> $unknown
     */
    public function __construct(array $unknown)
    {
        $this->unknown = $unknown;
        parent::__construct('Unknown template variables: ' . implode(', ', $unknown));
    }
}
