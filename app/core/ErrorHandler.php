<?php

declare(strict_types=1);

namespace App\Core;

use App\Libraries\Logger;
use Throwable;

/**
 * Global error and exception handler.
 *
 * Technical details are logged, never shown to the user. Users always receive
 * a friendly message. In debug mode (bootstrap-only flag) details are shown to
 * aid local development.
 */
final class ErrorHandler
{
    public function __construct(
        private readonly Logger $logger,
        private readonly bool $debug = false
    ) {
    }

    public function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', $this->debug ? '1' : '0');

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        $this->logger->error($message, ['level' => $level, 'file' => $file, 'line' => $line]);

        // Let PHP's internal handler continue for non-fatal notices.
        return false;
    }

    public function handleException(Throwable $e): void
    {
        $this->logger->error($e->getMessage(), [
            'exception' => $e::class,
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
        ]);

        $this->renderFriendly($e);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        if (in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $this->logger->emergency($error['message'], [
                'file' => $error['file'],
                'line' => $error['line'],
            ]);
        }
    }

    private function renderFriendly(Throwable $e): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        if ($this->debug) {
            echo '<pre style="padding:16px;font-family:monospace;">';
            echo e($e::class) . ': ' . e($e->getMessage()) . "\n";
            echo e($e->getFile()) . ':' . $e->getLine() . "\n\n";
            echo e($e->getTraceAsString());
            echo '</pre>';

            return;
        }

        // Friendly, translatable message. Falls back if translator unavailable.
        $message = function_exists('__') ? __('errors.generic') : 'Something went wrong.';
        echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Erro</title></head><body style="font-family:system-ui;padding:40px;">'
            . '<h1>' . e($message) . '</h1></body></html>';
    }
}
