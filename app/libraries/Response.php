<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * HTTP response helper.
 *
 * Centralises HTML and JSON output. Every API response follows the standard
 * envelope defined by the Master Specification:
 *   { success, message, data, errors, meta }
 */
final class Response
{
    /**
     * Send an HTML string response.
     */
    public function html(string $content, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo $content;
    }

    /**
     * Send a standardised JSON response.
     *
     * @param array<string, mixed>|list<mixed>|null $data
     * @param array<string, mixed>|list<mixed>       $errors
     * @param array<string, mixed>                   $meta
     */
    public function json(
        bool $success,
        string $message = '',
        array|null $data = null,
        array $errors = [],
        array $meta = [],
        int $status = 200
    ): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
            'errors'  => $errors,
            'meta'    => $meta,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Convenience for successful JSON responses.
     *
     * @param array<string, mixed>|list<mixed>|null $data
     * @param array<string, mixed>                  $meta
     */
    public function success(string $message = '', array|null $data = null, array $meta = [], int $status = 200): void
    {
        $this->json(true, $message, $data, [], $meta, $status);
    }

    /**
     * Convenience for error JSON responses.
     *
     * @param array<string, mixed>|list<mixed> $errors
     */
    public function error(string $message = '', array $errors = [], int $status = 400): void
    {
        $this->json(false, $message, null, $errors, [], $status);
    }

    /**
     * Redirect to another path.
     */
    public function redirect(string $location, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $location);
    }
}
