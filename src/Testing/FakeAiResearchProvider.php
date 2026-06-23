<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Testing;

use Gawrys\Counterparty\Ai\Research\AiResearchProvider;
use Gawrys\Counterparty\Ai\Research\ResearchRequest;
use Gawrys\Counterparty\Ai\Research\ResearchResult;

/**
 * Deterministic, offline {@see AiResearchProvider} for tests. Returns a recorded
 * {@see ResearchResult} per counterparty fingerprint (a "cassette"), or a default,
 * and counts calls so cache behaviour can be asserted.
 */
final class FakeAiResearchProvider implements AiResearchProvider
{
    /** @var array<string, ResearchResult> */
    private array $cassettes = [];

    public int $calls = 0;

    public function __construct(private readonly ?ResearchResult $default = null)
    {
    }

    public function record(string $fingerprint, ResearchResult $result): void
    {
        $this->cassettes[$fingerprint] = $result;
    }

    public function research(ResearchRequest $request, array $tools): ResearchResult
    {
        ++$this->calls;

        return $this->cassettes[$request->counterparty->fingerprint()]
            ?? $this->default
            ?? new ResearchResult([], 'No research recorded.');
    }
}
