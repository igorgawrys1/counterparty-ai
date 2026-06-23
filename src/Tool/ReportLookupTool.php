<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tool;

use Gawrys\Counterparty\Report\CheckResult;
use Gawrys\Counterparty\Report\VerificationReport;

/**
 * Exposes the finished verification report (the hard facts) to the model as read-only
 * ground truth. The AI may contextualise these facts but must never contradict them.
 */
final readonly class ReportLookupTool implements ContextAwareTool
{
    public function __construct(private ?VerificationReport $report = null)
    {
    }

    public function forContext(ResearchContext $context): static
    {
        return new self($context->report);
    }

    public function name(): string
    {
        return 'verification_report';
    }

    public function description(): string
    {
        return 'Return the deterministic verification results already gathered (the ground truth).';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
        ];
    }

    public function execute(array $arguments): ToolResult
    {
        $report = $this->report ?? new VerificationReport();

        $results = array_map(
            static fn (CheckResult $r): array => [
                'source' => $r->source,
                'check' => $r->checkName,
                'status' => $r->status->value,
                'summary' => $r->summary,
            ],
            $report->results(),
        );

        return ToolResult::ok(['results' => $results]);
    }
}
