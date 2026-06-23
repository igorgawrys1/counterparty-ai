<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Research;

use Gawrys\Counterparty\Ai\Tool\ResearchTool;
use Gawrys\Counterparty\Http\JsonHttpClient;
use Gawrys\Counterparty\Support\ArrayReader;
use Psr\Log\LoggerInterface;

/**
 * Reference {@see AiResearchProvider} backed by the Anthropic Messages API, over PSR-18
 * (no SDK). Exposes the research tools for NATIVE tool use: the model calls
 * `registry_lookup` / `web_search` / `verification_report`, this provider executes them and
 * feeds the results back, looping until the model returns the final findings JSON. The
 * inherited parse/validate/retry loop rejects anything that does not parse.
 *
 * @see https://docs.anthropic.com/en/api/messages
 */
final readonly class AnthropicResearchProvider extends AbstractAiResearchProvider
{
    public function __construct(
        private JsonHttpClient $http,
        #[\SensitiveParameter]
        private string $apiKey,
        private string $model = 'claude-haiku-4-5',
        private string $baseUri = 'https://api.anthropic.com',
        private string $apiVersion = '2023-06-01',
        ResearchResultParser $parser = new ResearchResultParser(),
        int $maxAttempts = 2,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($parser, $maxAttempts, $logger);
    }

    protected function complete(ResearchRequest $request, array $tools): string
    {
        $headers = ['x-api-key' => $this->apiKey, 'anthropic-version' => $this->apiVersion];
        $toolDefs = array_map(
            static fn (ResearchTool $t): array => ['name' => $t->name(), 'description' => $t->description(), 'input_schema' => $t->schema()],
            $tools,
        );

        /** @var list<array<string, mixed>> $messages */
        $messages = [['role' => 'user', 'content' => $request->userPrompt]];
        $blocks = [];

        for ($hop = 0; $hop <= $this->maxToolHops; ++$hop) {
            $body = [
                'model' => $this->model,
                'max_tokens' => $request->maxTokens,
                'system' => $request->systemPrompt,
                'messages' => $messages,
            ];
            if ($toolDefs !== []) {
                $body['tools'] = $toolDefs;
            }

            $response = $this->http->postJson(rtrim($this->baseUri, '/') . '/v1/messages', $body, $headers);
            $blocks = $this->objectList($response, 'content');
            $toolUses = array_values(array_filter($blocks, static fn (array $b): bool => ($b['type'] ?? null) === 'tool_use'));

            if ($toolUses === []) {
                return $this->concatText($blocks);
            }

            $messages[] = ['role' => 'assistant', 'content' => $blocks];

            $results = [];
            foreach ($toolUses as $toolUse) {
                $reader = ArrayReader::of($toolUse);
                $result = $this->runTool(
                    $tools,
                    $reader->string('name') ?? '',
                    $this->stringKeyedArguments($reader->nested('input')->toArray()),
                );
                $results[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $reader->string('id') ?? '',
                    'content' => $this->encodeToolResult($result),
                ];
            }
            $messages[] = ['role' => 'user', 'content' => $results];
        }

        return $this->concatText($blocks);
    }

    /**
     * @param list<array<array-key, mixed>> $blocks
     */
    private function concatText(array $blocks): string
    {
        $text = '';
        foreach ($blocks as $block) {
            if (($block['type'] ?? null) === 'text' && \is_string($block['text'] ?? null)) {
                /** @var string $value */
                $value = $block['text'];
                $text .= $value;
            }
        }

        return $text;
    }
}
