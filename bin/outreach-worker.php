<?php

declare(strict_types=1);

/**
 * Outreach worker (CLI).
 *
 * Processes the outreach queue asynchronously:
 *   - send_message : dispatch an outbox message through its channel provider.
 *   - follow_up    : advance a sequence enrollment (prepare the next step).
 * Between claims it also advances due sequence enrollments, so follow-ups fire
 * on time without any web request.
 *
 * Sending NEVER happens inside a web request — this script is the engine.
 *
 * Usage:
 *   php bin/outreach-worker.php           # drain the queue, then exit (cron-friendly)
 *   php bin/outreach-worker.php --loop    # keep polling
 *   php bin/outreach-worker.php --once    # one job then exit
 *
 * Idempotent: atomic claim, retry with backoff, dead-job after max attempts,
 * stuck-job recovery. Deferred sends (outside window / rate / cooldown) are
 * re-queued with backoff instead of failing, so nothing is dropped or spammed.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

/** @var App\Core\Kernel $kernel */
$kernel = require dirname(__DIR__) . '/app/bootstrap.php';
$container = $kernel->boot();

use App\Repositories\OutreachEnrollmentRepository;
use App\Repositories\OutreachJobRepository;
use App\Services\ConfigService;
use App\Services\Outreach\SendService;
use App\Services\Outreach\SequenceService;

/** @var OutreachJobRepository $jobs */
$jobs = $container->get(OutreachJobRepository::class);
/** @var SendService $sender */
$sender = $container->get(SendService::class);
/** @var SequenceService $sequences */
$sequences = $container->get(SequenceService::class);
/** @var OutreachEnrollmentRepository $enrollments */
$enrollments = $container->get(OutreachEnrollmentRepository::class);
/** @var ConfigService $config */
$config = $container->get(ConfigService::class);
/** @var App\Libraries\Logger $logger */
$logger = $container->get('logger');

$loop = in_array('--loop', $argv, true);
$once = in_array('--once', $argv, true);

$workerId = gethostname() . ':' . getmypid() . ':' . bin2hex(random_bytes(4));
$stuckTimeout = (int) ($config->get('outreach_job_stuck_timeout', '600') ?? 600);

fwrite(STDOUT, "[outreach {$workerId}] started\n");

// Enqueue follow-up jobs for enrollments that are due to advance.
$scheduleDueEnrollments = static function () use ($enrollments, $jobs, $logger): void {
    foreach ($enrollments->dueEnrollments(50) as $enrollment) {
        $enrollmentId = (int) $enrollment['id'];
        $jobs->enqueue('follow_up', null, $enrollmentId, []);
        // Park the enrollment so it is not re-queued before the job runs.
        $enrollments->advance($enrollmentId, (int) $enrollment['current_step'], null);
        $logger->info('Follow-up enfileirado.', ['enrollment' => $enrollmentId]);
    }
};

$processOne = static function () use ($jobs, $sender, $sequences, $enrollments, $logger, $stuckTimeout, $workerId): bool {
    $requeued = $jobs->requeueStuck($stuckTimeout);
    if ($requeued > 0) {
        fwrite(STDOUT, "[outreach {$workerId}] requeued {$requeued} stuck job(s)\n");
    }

    $job = $jobs->claimNext($workerId);
    if ($job === null) {
        return false;
    }

    $id = (int) $job['id'];
    $type = (string) $job['type'];
    $messageId = $job['message_id'] !== null ? (int) $job['message_id'] : 0;
    $enrollmentId = $job['enrollment_id'] !== null ? (int) $job['enrollment_id'] : 0;

    fwrite(STDOUT, "[outreach {$workerId}] job #{$id} type={$type}\n");

    try {
        switch ($type) {
            case 'send_message':
                $result = $sender->dispatch($messageId);
                // Deferred (window/rate/cooldown): retry later with backoff.
                if (($result['status'] ?? '') === 'deferred') {
                    $jobs->fail($id, 'deferred:' . ($result['reason'] ?? ''), (int) $job['attempts'], 999);
                    $logger->info('Envio adiado.', ['message' => $messageId, 'reason' => $result['reason'] ?? '']);
                    return true;
                }
                $jobs->markDone($id);
                break;
            case 'follow_up':
                $enrollment = $enrollments->find($enrollmentId);
                if ($enrollment !== null && (string) $enrollment['status'] === 'active') {
                    $sequences->advance($enrollment);
                }
                $jobs->markDone($id);
                break;
            default:
                throw new RuntimeException('unknown_job_type:' . $type);
        }
    } catch (Throwable $e) {
        $jobs->fail($id, $e->getMessage(), (int) $job['attempts'], (int) $job['max_attempts']);
        $logger->error('Outreach job falhou.', ['job' => $id, 'type' => $type, 'error' => $e->getMessage()]);
    }

    return true;
};

if ($once) {
    $scheduleDueEnrollments();
    $processOne();
    fwrite(STDOUT, "[outreach {$workerId}] done (once)\n");
    exit(0);
}

if ($loop) {
    while (true) {
        $scheduleDueEnrollments();
        if (!$processOne()) {
            sleep(3);
        }
    }
}

$scheduleDueEnrollments();
$any = false;
while ($processOne()) {
    $any = true;
}
fwrite(STDOUT, "[outreach {$workerId}] done" . ($any ? '' : ' (nothing queued)') . "\n");
exit(0);
