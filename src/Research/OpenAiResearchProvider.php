<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Research;

use Gawrys\Counterparty\Ai\Tool\ResearchTool;
use Gawrys\Counterparty\Http\JsonHttpClient;
use Gawrys\Counterparty\Support\ArrayReader;
use Psr\Log\LoggerInterface;

/**
 * Reference {@see AiResearchProvider} backed by the OpenAI Chat Completions API, over
 * PSR-18 (no SDK). Exposes the research tools for NATIVE function calling: the model emits
 * `tool_calls`, this provider executes them and feeds the results back, looping until the
 * model returns the final findings JSON.
 *
 * @see https://platform.openai.com/docs/api-reference/chat
 */
final readonly class OpenAiResearchProvider extends AbstractAiResearchProvider
{
    public function __construct(
        private JsonHttpClient $http,
        #[\SensitiveParameter]
        private string $apiKey,
        private string $model = 'gpt-4o-mini',
        private string $baseUri = 'https://api.openai.com',
        ResearchResultParser $parser = new ResearchResultParser(),
        int $maxAttempts = 2,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($parser, $maxAttempts, $logger);
    }

    protected function complete(ResearchRequest $request, array $tools): string
    {
        $headers = ['Authorization' => 'Bearer ' . $this->apiKey];
        $toolDefs = array_map(
            static fn (ResearchTool $t): array => [
                'type' => 'function',
                'function' => ['name' => $t->name(), 'description' => $t->description(), 'parameters' => $t->schema()],
            ],
            $tools,
        );

        /** @var list<array<string, mixed>> $messages */
        $messages = [
            ['role' => 'system', 'content' => $request->systemPrompt],
            ['role' => 'user', 'content' => $request->userPrompt],
        ];

        for ($hop = 0; $hop <= $this->maxToolHops; ++$hop) {
            $body = [
                'model' => $this->model,
                'max_tokens' => $request->maxTokens,
                'response_format' => ['type' => 'json_object'],
                'messages' => $messages,
            ];
            if ($toolDefs !== []) {
                $body['tools'] = $toolDefs;
            }

            $response = $this->http->postJson(rtrim($this->baseUri, '/') . '/v1/chat/completions', $body, $headers);

            $choices = $this->objectList($response, 'choices');
            $first = $choices[0] ?? [];
            /** @var mixed $messageValue */
            $messageValue = $first['message'] ?? null;
            $message = \is_array($messageValue) ? $this->stringKeyedArguments($messageValue) : [];

            $toolCalls = $this->objectList($message, 'tool_calls');
            if ($toolCalls === []) {
                /** @var mixed $content */
                $content = $message['content'] ?? null;

                return \is_string($content) ? $content : '';
            }

            $messages[] = $message;
            foreach ($toolCalls as $call) {
                $reader = ArrayReader::of($call);
                $function = $reader->nested('function');
                $result = $this->runTool(
                    $tools,
                    $function->string('name') ?? '',
                    $this->decodeArguments($function->string('arguments') ?? ''),
                );
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $reader->string('id') ?? '',
                    'content' => $this->encodeToolResult($result),
                ];
            }
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeArguments(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return \is_array($decoded) ? $this->stringKeyedArguments($decoded) : [];
    }
}
