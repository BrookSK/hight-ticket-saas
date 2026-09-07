<?php

declare(strict_types=1);

namespace App\Libraries\Http;

/**
 * Immutable HTTP response value object returned by HttpClient.
 */
final class HttpResponse
{
    /**
     * @param array<string, string> $headers Lower-cased header name => value.
     */
    public function __construct(
        public readonly string $finalUrl,
        public readonly int $statusCode,
        public readonly array $headers,
        public readonly string $body,
        public readonly float $responseTimeMs,
        public readonly int $redirectCount,
        public readonly bool $truncated,
        public readonly ?string $error = null
    ) {
    }

    public function ok(): bool
    {
        return $this->error === null && $this->statusCode >= 200 && $this->statusCode < 400;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function contentType(): string
    {
        return $this->header('content-type') ?? '';
    }

    public function isHtml(): bool
    {
        return str_contains(strtolower($this->contentType()), 'text/html');
    }
}
