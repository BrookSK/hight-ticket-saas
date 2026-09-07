<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Immutable-ish representation of the incoming HTTP request.
 *
 * Provides safe accessors for query, body, server and header data. Controllers
 * read input through this class; they never touch superglobals directly.
 */
final class Request
{
    /** @var array<string, mixed> */
    private array $query;

    /** @var array<string, mixed> */
    private array $body;

    /** @var array<string, mixed> */
    private array $server;

    /** @var array<string, mixed> */
    private array $files;

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, mixed> $server
     * @param array<string, mixed> $files
     */
    public function __construct(array $query, array $body, array $server, array $files)
    {
        $this->query = $query;
        $this->body = $body;
        $this->server = $server;
        $this->files = $files;
    }

    public static function fromGlobals(): self
    {
        $body = $_POST;

        // Support JSON request bodies for API endpoints.
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (is_string($contentType) && str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            if ($raw !== false && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $body = array_merge($body, $decoded);
                }
            }
        }

        return new self($_GET, $body, $_SERVER, $_FILES);
    }

    public function method(): string
    {
        $method = strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));

        // Support method override for HTML forms (_method field).
        if ($method === 'POST' && isset($this->body['_method'])) {
            $override = strtoupper((string) $this->body['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }

        return $method;
    }

    public function path(): string
    {
        $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        return '/' . trim($path, '/');
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function file(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $this->server[$key] ?? null;

        return $value === null ? null : (string) $value;
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function userAgent(): string
    {
        return (string) ($this->server['HTTP_USER_AGENT'] ?? '');
    }

    public function isSecure(): bool
    {
        $https = $this->server['HTTPS'] ?? '';
        if ($https !== '' && strtolower((string) $https) !== 'off') {
            return true;
        }

        return (string) ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    /**
     * Whether the client expects a JSON response (API request).
     */
    public function expectsJson(): bool
    {
        $accept = (string) ($this->server['HTTP_ACCEPT'] ?? '');
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        return str_starts_with($this->path(), '/api');
    }
}
