<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Libraries\Logger;
use Throwable;

/**
 * SMTP mail sender.
 *
 * Reads SMTP configuration from the database (Configurações Gerais) through
 * ConfigService — never from .env or constants. When SMTP is not configured,
 * sending is skipped gracefully (logged) so dependent flows never break.
 *
 * Implemented with native sockets to avoid extra dependencies; a driver
 * abstraction can be introduced later without changing callers.
 */
final class MailService extends Service
{
    /**
     * Send an HTML email. Returns true on success, false if skipped/failed.
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $config = $this->config();

        $host = (string) ($config->get('smtp_host') ?? '');
        $fromEmail = (string) ($config->get('smtp_from_email') ?? '');

        if ($host === '' || $fromEmail === '') {
            // Not configured yet: skip gracefully.
            $this->logger()->info('E-mail não enviado: SMTP não configurado.', [
                'to'      => $toEmail,
                'subject' => $subject,
            ]);

            return false;
        }

        $port = (int) ($config->get('smtp_port') ?? 587);
        $username = (string) ($config->get('smtp_username') ?? '');
        $password = (string) ($config->get('smtp_password') ?? '');
        $encryption = strtolower((string) ($config->get('smtp_encryption') ?? 'tls'));
        $fromName = (string) ($config->get('smtp_from_name') ?? 'LRV Web');

        try {
            return $this->deliver(
                $host,
                $port,
                $encryption,
                $username,
                $password,
                $fromEmail,
                $fromName,
                $toEmail,
                $toName,
                $subject,
                $htmlBody
            );
        } catch (Throwable $e) {
            $this->logger()->error('Falha ao enviar e-mail.', [
                'to'      => $toEmail,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Minimal SMTP conversation over a socket.
     */
    private function deliver(
        string $host,
        int $port,
        string $encryption,
        string $username,
        string $password,
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody
    ): bool {
        $transport = $encryption === 'ssl' ? 'ssl://' : '';
        $socket = @stream_socket_client(
            $transport . $host . ':' . $port,
            $errno,
            $errstr,
            15
        );

        if ($socket === false) {
            $this->logger()->error('Não foi possível conectar ao servidor SMTP.', [
                'host'  => $host,
                'errno' => $errno,
            ]);

            return false;
        }

        $this->read($socket);
        $this->command($socket, 'EHLO ' . $this->clientHost());

        if ($encryption === 'tls') {
            $this->command($socket, 'STARTTLS');
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->command($socket, 'EHLO ' . $this->clientHost());
        }

        if ($username !== '') {
            $this->command($socket, 'AUTH LOGIN');
            $this->command($socket, base64_encode($username));
            $this->command($socket, base64_encode($password));
        }

        $this->command($socket, 'MAIL FROM:<' . $fromEmail . '>');
        $this->command($socket, 'RCPT TO:<' . $toEmail . '>');
        $this->command($socket, 'DATA');

        // Headers + base64 body as a single DATA payload, terminated by CRLF.CRLF.
        $headers = $this->buildHeaders($fromEmail, $fromName, $toEmail, $toName, $subject);
        $encodedBody = chunk_split(base64_encode($htmlBody));
        $payload = $headers . "\r\n\r\n" . $encodedBody . "\r\n.";
        $this->command($socket, $payload);

        $this->command($socket, 'QUIT');
        fclose($socket);

        return true;
    }

    private function buildHeaders(string $fromEmail, string $fromName, string $toEmail, string $toName, string $subject): string
    {
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $encodedToName = '=?UTF-8?B?' . base64_encode($toName) . '?=';

        return implode("\r\n", [
            'Date: ' . date('r'),
            'From: ' . $encodedFromName . ' <' . $fromEmail . '>',
            'To: ' . $encodedToName . ' <' . $toEmail . '>',
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ]);
    }

    private function clientHost(): string
    {
        return gethostname() ?: 'localhost';
    }

    /**
     * @param resource $socket
     */
    private function command($socket, string $command): string
    {
        fwrite($socket, $command . "\r\n");

        return $this->read($socket);
    }

    /**
     * @param resource $socket
     */
    private function read($socket): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            // SMTP multiline responses have a '-' after the code; final has space.
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        return $response;
    }

    private function config(): ConfigService
    {
        /** @var ConfigService $config */
        $config = $this->container->get(ConfigService::class);

        return $config;
    }

    private function logger(): Logger
    {
        /** @var Logger $logger */
        $logger = $this->container->get('logger');

        return $logger;
    }
}
