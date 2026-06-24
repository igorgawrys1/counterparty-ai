<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tool\WebSearch;

final readonly class WebSearchResult
{
    public function __construct(
        public string $title,
        public string $url,
        public string $snippet,
    ) {
    }
}
