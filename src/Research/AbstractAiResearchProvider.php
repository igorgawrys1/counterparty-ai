<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Research;

use Gawrys\Counterparty\Ai\Exception\InvalidResearchResult;
use Gawrys\Counterparty\Ai\Tool\ResearchTool;
use Gawrys\Counterparty\Ai\Tool\ToolResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Base for real LLM adapters. Handles the force-JSON / parse / validate / retry loop so
 * concrete providers only implement {@see self::complete()} (the SDK call). Malformed
 * output is retried, never trusted; on exhaustion the failure surfaces as an exception
 * for the strategy to turn into "human review required".
 */
abstract readonly class AbstractAiResearchProvider implements AiResearchProvider
{
    private LoggerInterface $logger;

    public function __construct(
        private ResearchResultParser $parser,
        private int $maxAttempts = 2,
        ?LoggerInterface $logger = null,
        protected int $maxToolHops = 4,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    final public function research(ResearchRequest $request, array $tools): ResearchResult
    {
        $lastError = null;

        for ($attempt = 1; $attempt <= max(1, $this->maxAttempts); ++$attempt) {
            $raw = $this->complete($request, $tools);

            try {
                /** @var mixed $decoded */
                $decoded = json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
                if (!\is_array($decoded)) {
                    throw InvalidResearchResult::missingClaim(0);
                }

                return $this->parser->parse($decoded);
            } catch (\JsonException|InvalidResearchResult $e) {
                $lastError = $e;
                $this->logger->warning('AI returned malformed structured output; retrying.', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw $lastError instanceof InvalidResearchResult
            ? $lastError
            : InvalidResearchResult::missingClaim(0);
    }

    /**
     * Perform the model call and return the raw JSON string. Implementations should pass
     * {@see ResearchRequest::$systemPrompt}/$userPrompt and expose the tools for function
     * calling, requesting structured/JSON output.
     *
     * @param list<ResearchTool> $tools
     */
    abstract protected function complete(ResearchRequest $request, array $tools): string;

    /**
     * Dispatch a model-requested tool call to the matching {@see ResearchTool}.
     *
     * @param list<ResearchTool> $tools
     * @param array<string, mixed> $arguments
     */
    final protected function runTool(array $tools, string $name, array $arguments): ToolResult
    {
        foreach ($tools as $tool) {
            if ($tool->name() === $name) {
                return $tool->execute($arguments);
            }
        }

        return ToolResult::failed(\sprintf('Unknown tool "%s".', $name));
    }

    /**
     * Serialise a tool result for feeding back to the model. Includes the source URL so the
     * model can ground any claim it derives.
     */
    final protected function encodeToolResult(ToolResult $result): string
    {
        $payload = $result->ok
            ? ['ok' => true, 'data' => $result->data, 'source_url' => $result->sourceUrl]
            : ['ok' => false, 'error' => $result->error];

        try {
            return json_encode($payload, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return '{"ok":false,"error":"unserialisable tool result"}';
        }
    }

    /**
     * Extract the array-valued elements of $key as a list of raw arrays (e.g. response
     * content blocks, tool calls, choices).
     *
     * @param array<string, mixed> $data
     *
     * @return list<array<array-key, mixed>>
     */
    final protected function objectList(array $data, string $key): array
    {
        $value = $data[$key] ?? null;
        if (!\is_array($value)) {
            return [];
        }

        $out = [];
        /** @var mixed $item */
        foreach ($value as $item) {
            if (\is_array($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * Narrow a decoded JSON object to string keys for {@see ResearchTool::execute()}.
     *
     * @param array<array-key, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    final protected function stringKeyedArguments(array $arguments): array
    {
        $keys = array_map(static fn (int|string $key): string => (string) $key, array_keys($arguments));

        return array_combine($keys, array_values($arguments));
    }
}
