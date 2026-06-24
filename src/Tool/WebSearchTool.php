<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tool;

use Gawrys\Counterparty\Ai\Tool\WebSearch\WebSearchClient;
use Gawrys\Counterparty\Ai\Tool\WebSearch\WebSearchResult;
use Gawrys\Counterparty\Support\ArrayReader;

/**
 * Adverse-media / open-web search tool. Every hit carries its URL, so findings derived
 * from it are grounded by construction.
 */
final readonly class WebSearchTool implements ResearchTool
{
    public function __construct(
        private WebSearchClient $client,
        private int $limit = 5,
    ) {
    }

    public function name(): string
    {
        return 'web_search';
    }

    public function description(): string
    {
        return 'Search the open web for adverse media about the counterparty. Returns titled results with URLs.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'The search query.'],
            ],
            'required' => ['query'],
        ];
    }

    public function execute(array $arguments): ToolResult
    {
        $query = ArrayReader::of($arguments)->string('query');
        if ($query === null || trim($query) === '') {
            return ToolResult::failed('A non-empty "query" argument is required.');
        }

        $hits = array_map(
            static fn (WebSearchResult $r): array => ['title' => $r->title, 'url' => $r->url, 'snippet' => $r->snippet],
            $this->client->search($query, $this->limit),
        );

        return ToolResult::ok(['query' => $query, 'results' => $hits]);
    }
}
