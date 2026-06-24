<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests\Fixture;

use Gawrys\Counterparty\Ai\Tool\WebSearch\WebSearchClient;
use Gawrys\Counterparty\Ai\Tool\WebSearch\WebSearchResult;

final class FakeWebSearchClient implements WebSearchClient
{
    /**
     * @param list<WebSearchResult> $results
     */
    public function __construct(private readonly array $results = [])
    {
    }

    public function search(string $query, int $limit = 5): array
    {
        return \array_slice($this->results, 0, $limit);
    }
}
