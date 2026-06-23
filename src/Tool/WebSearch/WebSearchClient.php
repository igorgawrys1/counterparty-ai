<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tool\WebSearch;

/**
 * Port for an adverse-media / open-web search backend. Application-provided; this package
 * ships no live implementation (search providers vary and are licensed separately).
 */
interface WebSearchClient
{
    /**
     * @return list<WebSearchResult>
     */
    public function search(string $query, int $limit = 5): array;
}
