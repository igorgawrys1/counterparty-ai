<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests\Fixture;

use Gawrys\Counterparty\Ai\Exception\InvalidResearchResult;
use Gawrys\Counterparty\Ai\Research\AiResearchProvider;
use Gawrys\Counterparty\Ai\Research\ResearchRequest;
use Gawrys\Counterparty\Ai\Research\ResearchResult;

final readonly class ThrowingAiProvider implements AiResearchProvider
{
    public function research(ResearchRequest $request, array $tools): ResearchResult
    {
        throw InvalidResearchResult::missingClaim(0);
    }
}
