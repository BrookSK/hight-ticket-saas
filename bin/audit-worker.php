<?php

declare(strict_types=1);

/**
 * Audit worker (CLI).
 *
 * Processes queued audits asynchronously. The full crawl NEVER runs inside a
 * web request — this script is the processing engine.
 *
 * Usage:
 *   php bin/audit-worker.php            # process all currently queued audits, then exit
 *   php bin/audit-worker.php --loop     # keep polling for new audits (daemon-like)
 *   php bin/audit-worker.php --once     # process a single audit then exit
 *
 * Designed to be safe under cron (run once per minute) AND as a long-running
 * loop. Idempotent: claiming is atomic, stuck audits are requeued, and a killed
 * worker never leaves an audit permanently locked.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

/** @var App\Core\Kernel $kernel */
$kernel = require dirname(__DIR__) . '/app/bootstrap.php';
$container = $kernel->boot();

use App\Repositories\AuditRepository;
use App\Services\AuditService;
use App\Services\ConfigService;

/** @var AuditRepository $audits */
$audits = $container->get(AuditRepository::class);
/** @var AuditService $service */
$service = $container->get(AuditService::class);
/** @var ConfigService $config */
$config = $container->get(ConfigService::class);

$loop = in_array('--loop', $argv, true);
$once = in_array('--once', $argv, true);

$workerId = gethostname() . ':' . getmypid() . ':' . bin2hex(random_bytes(4));
$stuckTimeout = (int) ($config->get('audit_stuck_timeout', '600') ?? 600);

fwrite(STDOUT, "[worker {$workerId}] started\n");

/**
 * Process one claim cycle. Returns true if an audit was processed.
 */
$processOne = static function () use ($audits, $service, $stuckTimeout, $workerId): bool {
    // Recover audits abandoned by a dead worker (idempotent).
    $requeued = $audits->requeueStuck($stuckTimeout);
    if ($requeued > 0) {
        fwrite(STDOUT, "[worker {$workerId}] requeued {$requeued} stuck audit(s)\n");
    }

    $auditId = $audits->claimNextQueued($workerId);
    if ($auditId === null) {
        return false;
    }

    fwrite(STDOUT, "[worker {$workerId}] processing audit #{$auditId}\n");
    $service->process($auditId, $workerId);
    fwrite(STDOUT, "[worker {$workerId}] finished audit #{$auditId}\n");

    return true;
};

if ($once) {
    $processOne();
    fwrite(STDOUT, "[worker {$workerId}] done (once)\n");
    exit(0);
}

if ($loop) {
    // Long-running mode: poll continuously.
    while (true) {
        $processed = $processOne();
        if (!$processed) {
            sleep(3);
        }
    }
}

// Default (cron-friendly): drain the current queue, then exit.
$processedAny = false;
while ($processOne()) {
    $processedAny = true;
}
fwrite(STDOUT, "[worker {$workerId}] done" . ($processedAny ? '' : ' (nothing queued)') . "\n");
exit(0);
