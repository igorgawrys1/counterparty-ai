<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Research;

use Gawrys\Counterparty\Ai\Tool\ResearchTool;

/**
 * Port for the LLM backend. Implementations force structured JSON output, validate it,
 * and retry on malformed responses — they never return un-parsed prose. The bundled
 * {@see \Gawrys\Counterparty\Ai\Testing\FakeAiResearchProvider} satisfies this port from
 * recorded cassettes so tests are deterministic and offline.
 */
interface AiResearchProvider
{
    /**
     * @param list<ResearchTool> $tools
     */
    public function research(ResearchRequest $request, array $tools): ResearchResult;
}
