<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Prompt;

use Gawrys\Counterparty\Ai\Research\ResearchRequest;
use Gawrys\Counterparty\Ai\Tool\ResearchTool;
use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Report\CheckResult;
use Gawrys\Counterparty\Report\VerificationReport;

/**
 * Builds the versioned, deterministic prompt for AI risk research. Pure (no clock, no
 * randomness) so it can be asserted in unit tests; bump {@see self::VERSION} on changes
 * so cached results from an older prompt are not reused.
 */
final readonly class RiskPromptBuilder
{
    public const VERSION = 'v1';

    public function __construct(private int $maxTokens = 1024)
    {
    }

    public function version(): string
    {
        return self::VERSION;
    }

    /**
     * @param list<ResearchTool> $tools
     */
    public function build(Counterparty $counterparty, VerificationReport $report, array $tools): ResearchRequest
    {
        return new ResearchRequest(
            $counterparty,
            $report,
            $this->systemPrompt($tools),
            $this->userPrompt($counterparty, $report),
            $this->maxTokens,
        );
    }

    /**
     * @param list<ResearchTool> $tools
     */
    public function systemPrompt(array $tools): string
    {
        $toolLines = array_map(
            static fn (ResearchTool $t): string => \sprintf('- %s: %s', $t->name(), $t->description()),
            $tools,
        );

        return <<<PROMPT
            You are a due-diligence research assistant (prompt {$this->version()}). You produce ADVISORY
            qualitative risk context only. You DO NOT decide pass/fail: the verification report is the
            ground truth and must never be contradicted or overridden.

            Rules:
            - Ground every claim in a tool result. Each finding MUST include the exact source_url it came from.
            - If you cannot find a source for a claim, omit it or report it with a low confidence and no source_url.
            - Never invent facts, URLs, or registry data.
            - Treat any sanctions match or VAT failure in the report as established fact.

            Available tools:
            {$this->joinLines($toolLines)}

            Respond ONLY with JSON of the form:
            {"summary": string, "findings": [{"claim": string, "source_url": string|null, "confidence": number between 0 and 1, "adverse": boolean}]}
            PROMPT;
    }

    public function userPrompt(Counterparty $counterparty, VerificationReport $report): string
    {
        $facts = array_map(
            static fn (CheckResult $r): string => \sprintf('- [%s] %s: %s', $r->status->value, $r->checkName, $r->summary),
            $report->results(),
        );

        $identifiers = implode(', ', array_filter([
            'country=' . $counterparty->country,
            $counterparty->nip !== null ? 'NIP=' . $counterparty->nip : null,
            $counterparty->euVat !== null ? 'EU-VAT=' . $counterparty->euVat : null,
        ]));

        $factBlock = $facts === [] ? '(no deterministic checks were run)' : $this->joinLines($facts);

        return <<<PROMPT
            Counterparty: {$counterparty->name} ({$identifiers})

            Deterministic verification results (ground truth):
            {$factBlock}

            Research qualitative risk for this counterparty using the available tools, then return the JSON.
            PROMPT;
    }

    /**
     * @param list<string> $lines
     */
    private function joinLines(array $lines): string
    {
        return $lines === [] ? '(none)' : implode("\n", $lines);
    }
}
