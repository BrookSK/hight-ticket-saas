<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * CSRF protection.
 *
 * Generates and validates a per-session token. Every state-changing form and
 * unsafe request must include the token (field name "_token" or header
 * "X-CSRF-Token"). Validation is enforced by CsrfMiddleware.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public function validate(?string $token): bool
    {
        $stored = $this->session->get(self::SESSION_KEY);

        return is_string($stored)
            && is_string($token)
            && $token !== ''
            && hash_equals($stored, $token);
    }
}
