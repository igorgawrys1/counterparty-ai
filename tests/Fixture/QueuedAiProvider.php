<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests\Fixture;

use Gawrys\Counterparty\Ai\Research\AbstractAiResearchProvider;
use Gawrys\Counterparty\Ai\Research\ResearchRequest;
use Gawrys\Counterparty\Ai\Research\ResearchResultParser;

/**
 * Concrete {@see AbstractAiResearchProvider} that returns pre-queued raw JSON strings,
 * to exercise the parse/validate/retry loop without a live model.
 */
final readonly class QueuedAiProvider extends AbstractAiResearchProvider
{
    private StringQueue $responses;

    /**
     * @param list<string> $responses
     */
    public function __construct(array $responses, int $maxAttempts = 2)
    {
        parent::__construct(new ResearchResultParser(), $maxAttempts);
        $this->responses = new StringQueue($responses);
    }

    protected function complete(ResearchRequest $request, array $tools): string
    {
        return $this->responses->next();
    }
}
