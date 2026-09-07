<?php

declare(strict_types=1);

namespace App\Libraries\Http;

/**
 * Safe outbound HTTP client (cURL primary, stream fallback).
 *
 * Every request is validated through SsrfGuard. Redirects are followed
 * manually so each hop is re-validated (blocking redirect-based SSRF), the
 * validated IP is pinned via cURL's resolve list (anti-rebinding), and the
 * response body is capped to a maximum size. All requests have connect and
 * total timeouts and an identifiable User-Agent.
 */
final class HttpClient
{
    public function __construct(
        private readonly SsrfGuard $guard,
        private readonly string $userAgent = 'LRVWebAuditBot/1.0',
        private readonly int $connectTimeout = 10,
        private readonly int $totalTimeout = 15,
        private readonly int $maxBytes = 3145728,
        private readonly int $maxRedirects = 5
    ) {
    }

    /**
     * Perform a GET request (follows redirects, downloads body up to maxBytes).
     */
    public function get(string $url): HttpResponse
    {
        return $this->request('GET', $url);
    }

    /**
     * Perform a HEAD request (no body). Useful for link checking cheaply.
     */
    public function head(string $url): HttpResponse
    {
        return $this->request('HEAD', $url);
    }

    private function request(string $method, string $url, int $redirectCount = 0): HttpResponse
    {
        try {
            $validated = $this->guard->validate($url);
        } catch (SsrfException $e) {
            return $this->errorResponse($url, 'blocked:' . $e->getMessage());
        }

        if (!function_exists('curl_init')) {
            return $this->streamRequest($method, $validated, $redirectCount);
        }

        return $this->curlRequest($method, $validated, $redirectCount);
    }

    /**
     * @param array{url:string, scheme:string, host:string, port:int, ips:list<string>} $v
     */
    private function curlRequest(string $method, array $v, int $redirectCount): HttpResponse
    {
        $start = microtime(true);
        $ch = curl_init();

        // Pin the validated IP so DNS cannot be rebound between check and connect.
        $pinnedIp = $v['ips'][0];
        curl_setopt($ch, CURLOPT_RESOLVE, [$v['host'] . ':' . $v['port'] . ':' . $pinnedIp]);

        curl_setopt_array($ch, [
            CURLOPT_URL            => $v['url'],
            CURLOPT_NOBODY         => $method === 'HEAD',
            CURLOPT_FOLLOWLOCATION => false, // handled manually for re-validation
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT        => $this->totalTimeout,
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_ACCEPT_ENCODING => '', // advertise gzip/br, let cURL decode
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);

        $headers = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function ($curl, $line) use (&$headers): int {
            $trimmed = trim($line);
            if ($trimmed !== '' && str_contains($trimmed, ':')) {
                [$name, $value] = explode(':', $trimmed, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
            return strlen($line);
        });

        // Cap the body size during download.
        $body = '';
        $truncated = false;
        if ($method !== 'HEAD') {
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($curl, $chunk) use (&$body, &$truncated): int {
                $body .= $chunk;
                if (strlen($body) > $this->maxBytes) {
                    $body = substr($body, 0, $this->maxBytes);
                    $truncated = true;
                    return -1; // abort transfer
                }
                return strlen($chunk);
            });
        }

        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errno = curl_errno($ch);
        curl_close($ch);

        $elapsed = (microtime(true) - $start) * 1000;

        // Aborting for size limit yields CURLE_WRITE_ERROR (23); that's expected.
        if ($errno !== 0 && !($truncated && $errno === CURLE_WRITE_ERROR)) {
            return $this->errorResponse($v['url'], 'curl_error:' . $errno, $elapsed);
        }

        // Handle redirects manually with re-validation.
        if ($status >= 300 && $status < 400 && isset($headers['location'])) {
            if ($redirectCount >= $this->maxRedirects) {
                return $this->errorResponse($v['url'], 'too_many_redirects', $elapsed);
            }
            $next = $this->resolveLocation($v['url'], $headers['location']);

            return $this->request($method, $next, $redirectCount + 1);
        }

        return new HttpResponse(
            finalUrl: $v['url'],
            statusCode: $status,
            headers: $headers,
            body: $body,
            responseTimeMs: round($elapsed, 2),
            redirectCount: $redirectCount,
            truncated: $truncated
        );
    }

    /**
     * Stream fallback when cURL is unavailable. Still validated by SsrfGuard,
     * but without IP pinning; redirects are handled manually and re-validated.
     *
     * @param array{url:string, scheme:string, host:string, port:int, ips:list<string>} $v
     */
    private function streamRequest(string $method, array $v, int $redirectCount): HttpResponse
    {
        $start = microtime(true);
        $context = stream_context_create([
            'http' => [
                'method'        => $method,
                'user_agent'    => $this->userAgent,
                'timeout'       => $this->totalTimeout,
                'follow_location' => 0,
                'ignore_errors' => true,
                'protocol_version' => 1.1,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $stream = @fopen($v['url'], 'rb', false, $context);
        if ($stream === false) {
            return $this->errorResponse($v['url'], 'stream_open_failed', (microtime(true) - $start) * 1000);
        }

        $meta = stream_get_meta_data($stream);
        $headers = $this->parseStreamHeaders($meta['wrapper_data'] ?? []);
        $status = $this->statusFromStreamHeaders($meta['wrapper_data'] ?? []);

        $body = '';
        $truncated = false;
        if ($method !== 'HEAD') {
            while (!feof($stream)) {
                $body .= fread($stream, 8192);
                if (strlen($body) > $this->maxBytes) {
                    $body = substr($body, 0, $this->maxBytes);
                    $truncated = true;
                    break;
                }
            }
        }
        fclose($stream);

        $elapsed = (microtime(true) - $start) * 1000;

        if ($status >= 300 && $status < 400 && isset($headers['location'])) {
            if ($redirectCount >= $this->maxRedirects) {
                return $this->errorResponse($v['url'], 'too_many_redirects', $elapsed);
            }
            $next = $this->resolveLocation($v['url'], $headers['location']);

            return $this->request($method, $next, $redirectCount + 1);
        }

        return new HttpResponse(
            finalUrl: $v['url'],
            statusCode: $status,
            headers: $headers,
            body: $body,
            responseTimeMs: round($elapsed, 2),
            redirectCount: $redirectCount,
            truncated: $truncated
        );
    }

    private function resolveLocation(string $base, string $location): string
    {
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }

        $parts = parse_url($base);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        if (str_starts_with($location, '/')) {
            return $scheme . '://' . $host . $port . $location;
        }

        $path = $parts['path'] ?? '/';
        $dir = rtrim(substr($path, 0, strrpos($path, '/') ?: 0), '/');

        return $scheme . '://' . $host . $port . $dir . '/' . $location;
    }

    /**
     * @param array<int, string> $lines
     * @return array<string, string>
     */
    private function parseStreamHeaders(array $lines): array
    {
        $headers = [];
        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }

        return $headers;
    }

    /**
     * @param array<int, string> $lines
     */
    private function statusFromStreamHeaders(array $lines): int
    {
        foreach ($lines as $line) {
            if (preg_match('#^HTTP/\d\.\d\s+(\d{3})#', $line, $m)) {
                return (int) $m[1];
            }
        }

        return 0;
    }

    private function errorResponse(string $url, string $error, float $elapsed = 0.0): HttpResponse
    {
        return new HttpResponse(
            finalUrl: $url,
            statusCode: 0,
            headers: [],
            body: '',
            responseTimeMs: round($elapsed, 2),
            redirectCount: 0,
            truncated: false,
            error: $error
        );
    }
}
