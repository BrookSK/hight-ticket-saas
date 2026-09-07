<?php

declare(strict_types=1);

namespace App\Libraries\Audit\Analyzers;

use App\Libraries\Audit\AuditContext;

/**
 * Contract for audit analyzers.
 *
 * Each analyzer inspects the already-collected data in the AuditContext and
 * appends issues/metrics/technologies. Analyzers are deterministic (no IA) and
 * must not perform the crawl themselves — they only read collected data plus,
 * when needed, make targeted follow-up requests via injected collaborators.
 *
 * New analyzers can be added without changing the pipeline.
 */
interface AnalyzerInterface
{
    /**
     * Machine key/category this analyzer contributes to (e.g. "seo").
     */
    public function category(): string;

    /**
     * Run the analysis, mutating the context.
     */
    public function analyze(AuditContext $context): void;
}
