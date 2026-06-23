<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Research;

use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Report\VerificationReport;

/**
 * Everything an {@see AiResearchProvider} needs for one research run. The report is the
 * ground truth the model must respect; the prompts are produced by a versioned
 * {@see \Gawrys\Counterparty\Ai\Prompt\RiskPromptBuilder}.
 */
final readonly class ResearchRequest
{
    public function __construct(
        public Counterparty $counterparty,
        public VerificationReport $report,
        public string $systemPrompt,
        public string $userPrompt,
        public int $maxTokens = 1024,
    ) {
    }
}
