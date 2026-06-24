<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Research;

/**
 * One qualitative observation produced by AI research. A finding without a source URL is
 * ungrounded and MUST NOT be promoted to evidence - the strategy treats it as a reason
 * for human review, not as a confirmed fact.
 */
final readonly class Finding
{
    /**
     * @param float $confidence value in [0.0, 1.0]
     */
    public function __construct(
        public string $claim,
        public ?string $sourceUrl,
        public float $confidence,
        public bool $adverse,
    ) {
    }

    public function isGrounded(): bool
    {
        return $this->sourceUrl !== null && $this->sourceUrl !== '';
    }
}
