<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Minimal file logger.
 *
 * Technical details always go to logs, never to the UI. Log files rotate by
 * day under storage/logs. Levels follow PSR-3 severity names.
 */
final class Logger
{
    private string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?? dirname(__DIR__, 2) . '/storage/logs';
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Write a log line. Context is JSON-encoded when present.
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0775, true);
        }

        $file = $this->directory . '/app-' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $encodedContext = $context !== []
            ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';

        $line = sprintf('[%s] %s: %s%s%s', $timestamp, strtoupper($level), $message, $encodedContext, PHP_EOL);

        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
