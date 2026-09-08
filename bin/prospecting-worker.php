<?php

declare(strict_types=1);

/**
 * Prospecting worker (CLI).
 *
 * Processes prospecting jobs (discovery → enrichment → audit → scoring)
 * asynchronously. Discovery NEVER runs inside a web request — this script is
 * the processing engine.
 *
 * Usage:
 *   php bin/prospecting-worker.php          # drain the queue, then exit (cron-friendly)
 *   php bin/prospecting-worker.php --loop    # keep polling
 *   php bin/prospecting-worker.php --once    # one job then exit
 *
 * Idempotent: atomic claim, retry with backoff, dead-job after max attempts,
 * stuck-job recovery. A single failing result never aborts the whole campaign.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

/** @var App\Core\Kernel $kernel */
$kernel = require dirname(__DIR__) . '/app/bootstrap.php';
$container = $kernel->boot();

use App\Repositories\ProspectingJobRepository;
use App\Services\ConfigService;
use App\Services\Prospecting\DiscoveryService;

/** @var ProspectingJobRepository $jobs */
$jobs = $container->get(ProspectingJobRepository::class);
/** @var DiscoveryService $discovery */
$discovery = $container->get(DiscoveryService::class);
/** @var ConfigService $config */
$config = $container->get(ConfigService::class);
/** @var App\Libraries\Logger $logger */
$logger = $container->get('logger');

$loop = in_array('--loop', $argv, true);
$once = in_array('--once', $argv, true);

$workerId = gethostname() . ':' . getmypid() . ':' . bin2hex(random_bytes(4));
$stuckTimeout = (int) ($config->get('prospecting_job_stuck_timeout', '600') ?? 600);

fwrite(STDOUT, "[prospecting {$workerId}] started\n");

$processOne = static function () use ($jobs, $discovery, $logger, $stuckTimeout, $workerId): bool {
    $requeued = $jobs->requeueStuck($stuckTimeout);
    if ($requeued > 0) {
        fwrite(STDOUT, "[prospecting {$workerId}] requeued {$requeued} stuck job(s)\n");
    }

    $job = $jobs->claimNext($workerId);
    if ($job === null) {
        return false;
    }

    $id = (int) $job['id'];
    $type = (string) $job['type'];
    $resultId = $job['result_id'] !== null ? (int) $job['result_id'] : 0;
    $campaignId = (int) $job['campaign_id'];

    fwrite(STDOUT, "[prospecting {$workerId}] job #{$id} type={$type}\n");
    $logger->info('Prospecting job iniciado.', ['job' => $id, 'type' => $type, 'campaign' => $campaignId]);

    try {
        switch ($type) {
            case 'discovery':
                $discovery->runDiscovery($campaignId);
                break;
            case 'enrichment':
                $discovery->runEnrichment($resultId);
                break;
            case 'audit':
                $discovery->runAudit($resultId);
                break;
            case 'scoring':
                $discovery->runScoring($resultId);
                break;
            default:
                throw new RuntimeException('unknown_job_type:' . $type);
        }
        $jobs->markDone($id);
    } catch (Throwable $e) {
        // Retry with backoff, or mark dead after max attempts. One failing job
        // never aborts the campaign.
        $jobs->fail($id, $e->getMessage(), (int) $job['attempts'], (int) $job['max_attempts']);
        $logger->error('Prospecting job falhou.', ['job' => $id, 'type' => $type, 'error' => $e->getMessage()]);
    }

    return true;
};

if ($once) {
    $processOne();
    fwrite(STDOUT, "[prospecting {$workerId}] done (once)\n");
    exit(0);
}

if ($loop) {
    while (true) {
        if (!$processOne()) {
            sleep(3);
        }
    }
}

$any = false;
while ($processOne()) {
    $any = true;
}
fwrite(STDOUT, "[prospecting {$workerId}] done" . ($any ? '' : ' (nothing queued)') . "\n");
exit(0);
