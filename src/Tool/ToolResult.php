<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tool;

/**
 * The output of a {@see ResearchTool}. Carries the data the model may reason over, plus
 * the source URL that grounds any claim derived from it - a finding with no source must
 * be treated as inconclusive, never as fact.
 */
final readonly class ToolResult
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public bool $ok,
        public array $data = [],
        public ?string $sourceUrl = null,
        public ?string $error = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function ok(array $data, ?string $sourceUrl = null): self
    {
        return new self(true, $data, $sourceUrl);
    }

    public static function failed(string $error): self
    {
        return new self(false, [], null, $error);
    }
}
