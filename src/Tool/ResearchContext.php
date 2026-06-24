<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tool;

use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Report\VerificationReport;

/**
 * The per-run context bound into {@see ContextAwareTool}s before research begins, so the
 * model cannot operate on an entity other than the one under verification.
 */
final readonly class ResearchContext
{
    public function __construct(
        public Counterparty $counterparty,
        public VerificationReport $report,
    ) {
    }
}
