<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Testing;

use Gawrys\Counterparty\Ai\Research\ResearchResult;
use Gawrys\Counterparty\Ai\Research\ResearchResultParser;

/**
 * Loads recorded AI responses from JSON cassette fixtures, validated through the same
 * parser used in production - so a cassette that would not parse in production cannot
 * silently pass in tests.
 */
final readonly class Cassette
{
    public function __construct(private ResearchResultParser $parser = new ResearchResultParser())
    {
    }

    public function fromJson(string $json): ResearchResult
    {
        /** @var mixed $decoded */
        $decoded = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        return $this->parser->parse(\is_array($decoded) ? $decoded : []);
    }

    public function fromFile(string $path): ResearchResult
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException(\sprintf('Unable to read cassette "%s".', $path));
        }

        return $this->fromJson($contents);
    }
}
