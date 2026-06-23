<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Research;

use Gawrys\Counterparty\Ai\Exception\InvalidResearchResult;
use Gawrys\Counterparty\Support\ArrayReader;

/**
 * Validates and converts the model's structured JSON into a {@see ResearchResult}.
 * Providers parse AND validate against this before trusting anything; malformed output
 * raises {@see InvalidResearchResult} so the provider can retry rather than guess.
 */
final readonly class ResearchResultParser
{
    /**
     * Expected shape:
     *  { "summary": string, "findings": [ { "claim": string, "source_url": string|null,
     *    "confidence": number 0..1, "adverse": bool } ] }.
     *
     * @param array<array-key, mixed> $data
     */
    public function parse(array $data): ResearchResult
    {
        $reader = ArrayReader::of($data);

        $findings = [];
        $index = 0;
        foreach ($reader->each('findings') as $row) {
            $claim = $row->string('claim');
            if ($claim === null || trim($claim) === '') {
                throw InvalidResearchResult::missingClaim($index);
            }

            $confidence = $row->float('confidence');
            if ($confidence === null || $confidence < 0.0 || $confidence > 1.0) {
                throw InvalidResearchResult::invalidConfidence($index);
            }

            $findings[] = new Finding(
                $claim,
                $row->string('source_url'),
                $confidence,
                $row->bool('adverse') ?? false,
            );

            ++$index;
        }

        return new ResearchResult($findings, $reader->string('summary') ?? '');
    }
}
