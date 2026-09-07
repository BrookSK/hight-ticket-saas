<?php

declare(strict_types=1);

namespace App\Libraries\Http;

use RuntimeException;

/**
 * Thrown when a URL/IP fails SSRF validation.
 *
 * The message is a stable machine code (e.g. "private_or_reserved_ip") so it can
 * be logged and mapped to a translatable, user-friendly message. Never expose
 * raw internal details to end users.
 */
final class SsrfException extends RuntimeException
{
}
