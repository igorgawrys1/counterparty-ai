<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests\Fixture;

use Gawrys\Counterparty\Ai\Tool\ResearchTool;
use Gawrys\Counterparty\Ai\Tool\ToolResult;

/**
 * A research tool that records the arguments it was called with, to assert that the
 * provider's native tool-use loop actually invoked it.
 */
final class RecordingTool implements ResearchTool
{
    /** @var list<array<string, mixed>> */
    public array $calls = [];

    public function __construct(private readonly string $name = 'registry_lookup')
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return 'Test tool.';
    }

    public function schema(): array
    {
        return ['type' => 'object', 'properties' => ['capability' => ['type' => 'string']], 'required' => ['capability']];
    }

    public function execute(array $arguments): ToolResult
    {
        $this->calls[] = $arguments;

        return ToolResult::ok(['found' => true], 'https://registry.example.test');
    }
}
