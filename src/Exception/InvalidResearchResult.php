<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Exception;

use Gawrys\Counterparty\Exception\CounterpartyException;

final class InvalidResearchResult extends \RuntimeException implements CounterpartyException
{
    public static function missingClaim(int $index): self
    {
        return new self(\sprintf('Finding #%d is missing a non-empty "claim".', $index));
    }

    public static function invalidConfidence(int $index): self
    {
        return new self(\sprintf('Finding #%d has a missing or out-of-range "confidence" (expected 0.0-1.0).', $index));
    }
}
