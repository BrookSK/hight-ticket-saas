<?php

declare(strict_types=1);

namespace App\Libraries\Http;

/**
 * Reusable SSRF protection layer.
 *
 * Validates and normalizes user-supplied URLs and ensures they do not point to
 * internal, private, reserved, link-local or cloud-metadata addresses. Designed
 * to be used by ANY feature that performs outbound requests, not just the
 * crawler.
 *
 * Anti-DNS-rebinding: resolve() returns the concrete IP(s) that passed
 * validation; the HTTP client must connect to one of those exact IPs (pinning)
 * so a later DNS change cannot redirect the request to an internal host.
 */
final class SsrfGuard
{
    /** Allowed URL schemes. */
    private const ALLOWED_SCHEMES = ['http', 'https'];

    /** Allowed destination ports. */
    private const ALLOWED_PORTS = [80, 443];

    /**
     * Validate and normalize a URL. Throws on any policy violation.
     *
     * @return array{url:string, scheme:string, host:string, port:int, ips:list<string>}
     * @throws SsrfException
     */
    public function validate(string $rawUrl): array
    {
        $url = trim($rawUrl);
        if ($url === '') {
            throw new SsrfException('empty_url');
        }

        // Add scheme if missing so parse_url behaves predictably.
        if (!preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $url)) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['host'])) {
            throw new SsrfException('invalid_url');
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        if (!in_array($scheme, self::ALLOWED_SCHEMES, true)) {
            throw new SsrfException('scheme_not_allowed');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            // Credentials in URL can be used to obfuscate the real host.
            throw new SsrfException('credentials_not_allowed');
        }

        $host = strtolower($parts['host']);
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        if (!in_array($port, self::ALLOWED_PORTS, true)) {
            throw new SsrfException('port_not_allowed');
        }

        // Reject raw IP literals that are already internal.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $this->assertPublicIp($host);
            $ips = [$host];
        } else {
            $this->assertValidHostname($host);
            $ips = $this->resolveAndValidate($host);
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $normalized = $scheme . '://' . $host . ($this->isDefaultPort($scheme, $port) ? '' : ':' . $port) . $path . $query;

        return [
            'url'    => $normalized,
            'scheme' => $scheme,
            'host'   => $host,
            'port'   => $port,
            'ips'    => $ips,
        ];
    }

    /**
     * Resolve a hostname to IPs and validate each. Returns the safe IP list.
     * Called again right before connecting (re-pinning) to mitigate rebinding.
     *
     * @return list<string>
     * @throws SsrfException
     */
    public function resolveAndValidate(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        $ips = [];

        if (is_array($records)) {
            foreach ($records as $record) {
                if (isset($record['ip'])) {
                    $ips[] = $record['ip'];
                } elseif (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        // Fallback to gethostbynamel for IPv4 when dns_get_record is limited.
        if ($ips === []) {
            $resolved = @gethostbynamel($host);
            if (is_array($resolved)) {
                $ips = $resolved;
            }
        }

        if ($ips === []) {
            throw new SsrfException('dns_resolution_failed');
        }

        foreach ($ips as $ip) {
            $this->assertPublicIp($ip);
        }

        return array_values(array_unique($ips));
    }

    /**
     * Assert an IP address is public/routable and not internal.
     *
     * @throws SsrfException
     */
    public function assertPublicIp(string $ip): void
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new SsrfException('invalid_ip');
        }

        // Reject private and reserved ranges (covers RFC1918, loopback, etc.).
        $isPublic = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($isPublic === false) {
            throw new SsrfException('private_or_reserved_ip');
        }

        // Explicitly block well-known dangerous addresses / metadata endpoints.
        if ($this->isBlockedIp($ip)) {
            throw new SsrfException('blocked_ip');
        }
    }

    private function isBlockedIp(string $ip): bool
    {
        // Cloud metadata (AWS/GCP/Azure/DO/Alibaba) and common internal hosts.
        $blockedExact = [
            '169.254.169.254', // cloud metadata
            '100.100.100.200', // Alibaba metadata
            '0.0.0.0',
        ];
        if (in_array($ip, $blockedExact, true)) {
            return true;
        }

        // IPv6 loopback / unspecified / unique-local / link-local.
        $lower = strtolower($ip);
        if (in_array($lower, ['::1', '::'], true)) {
            return true;
        }
        if (str_starts_with($lower, 'fe80:') // link-local
            || str_starts_with($lower, 'fc') // unique local fc00::/7
            || str_starts_with($lower, 'fd')
        ) {
            return true;
        }

        // IPv4-mapped IPv6 pointing to internal ranges.
        if (str_contains($lower, '::ffff:')) {
            $mapped = substr($lower, strrpos($lower, ':') + 1);
            if (filter_var($mapped, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $this->isBlockedIp($mapped)
                    || filter_var($mapped, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
            }
        }

        return false;
    }

    private function assertValidHostname(string $host): void
    {
        // Block obvious internal names.
        $blockedNames = ['localhost', 'localhost.localdomain'];
        if (in_array($host, $blockedNames, true)) {
            throw new SsrfException('blocked_host');
        }

        // Block hosts without a dot (internal short names) unless it's an IP.
        if (!str_contains($host, '.')) {
            throw new SsrfException('blocked_host');
        }

        // Basic hostname format check.
        if (!preg_match('/^[a-z0-9]([a-z0-9\-\.]*[a-z0-9])?$/i', $host)) {
            throw new SsrfException('invalid_host');
        }
    }

    private function isDefaultPort(string $scheme, int $port): bool
    {
        return ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);
    }
}
