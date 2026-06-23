<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tool;

/**
 * A capability the model may invoke during risk research (function calling). Tools are
 * the ONLY way the AI obtains facts; every claim it makes must trace back to a tool's
 * {@see ToolResult} and its source URL.
 */
interface ResearchTool
{
    /** Stable function name exposed to the model, e.g. "registry_lookup". */
    public function name(): string;

    public function description(): string;

    /**
     * JSON-schema for the tool arguments (the "parameters" object of a function spec).
     *
     * @return array<string, mixed>
     */
    public function schema(): array;

    /**
     * @param array<string, mixed> $arguments arguments produced by the model, matching {@see self::schema()}
     */
    public function execute(array $arguments): ToolResult;
}
