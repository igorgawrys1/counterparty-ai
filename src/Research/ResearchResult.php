<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Research;

/**
 * The validated, structured output of an {@see AiResearchProvider}.
 */
final readonly class ResearchResult
{
    /** @var list<Finding> */
    public array $findings;

    /**
     * @param iterable<Finding> $findings
     */
    public function __construct(
        iterable $findings = [],
        public string $summary = '',
    ) {
        $this->findings = \is_array($findings) ? array_values($findings) : iterator_to_array($findings, false);
    }

    /**
     * @return list<Finding>
     */
    public function grounded(): array
    {
        return array_values(array_filter($this->findings, static fn (Finding $f): bool => $f->isGrounded()));
    }

    public function hasUngrounded(): bool
    {
        foreach ($this->findings as $finding) {
            if (!$finding->isGrounded()) {
                return true;
            }
        }

        return false;
    }
}
