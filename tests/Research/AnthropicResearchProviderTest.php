<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests\Research;

use Gawrys\Counterparty\Ai\Research\AbstractAiResearchProvider;
use Gawrys\Counterparty\Ai\Research\AnthropicResearchProvider;
use Gawrys\Counterparty\Ai\Research\ResearchRequest;
use Gawrys\Counterparty\Ai\Tests\Fixture\MockHttp;
use Gawrys\Counterparty\Ai\Tests\Fixture\RecordingTool;
use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Report\VerificationReport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AnthropicResearchProvider::class)]
#[CoversClass(AbstractAiResearchProvider::class)]
final class AnthropicResearchProviderTest extends TestCase
{
    public function testParsesJsonFromMessagesResponse(): void
    {
        $http = new MockHttp();
        $http->queueJson([
            'content' => [
                ['type' => 'text', 'text' => '{"summary":"ok","findings":[{"claim":"Sanctioned in EU list","source_url":"https://eur-lex.test/1","confidence":0.9,"adverse":true}]}'],
            ],
            'stop_reason' => 'end_turn',
        ]);

        $provider = new AnthropicResearchProvider($http->jsonClient(), 'sk-test-key', 'claude-haiku-4-5');

        $result = $provider->research(
            new ResearchRequest(new Counterparty('Acme', 'PL'), new VerificationReport(), 'sys', 'user'),
            [],
        );

        self::assertCount(1, $result->grounded());
        self::assertSame('ok', $result->summary);
    }

    public function testSendsAuthHeaders(): void
    {
        $http = new MockHttp();
        $http->queueJson(['content' => [['type' => 'text', 'text' => '{"summary":"","findings":[]}']]]);

        (new AnthropicResearchProvider($http->jsonClient(), 'sk-secret'))->research(
            new ResearchRequest(new Counterparty('Acme', 'PL'), new VerificationReport(), 'sys', 'user'),
            [],
        );

        $requests = $http->client->getRequests();
        self::assertCount(1, $requests);
        self::assertSame('sk-secret', $requests[0]->getHeaderLine('x-api-key'));
        self::assertSame('2023-06-01', $requests[0]->getHeaderLine('anthropic-version'));
    }

    public function testNativeToolUseLoopExecutesToolsThenReturnsFindings(): void
    {
        $http = new MockHttp();
        // Hop 0: the model asks to call the registry tool.
        $http->queueJson([
            'content' => [['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'registry_lookup', 'input' => ['capability' => 'vat_status']]],
            'stop_reason' => 'tool_use',
        ]);
        // Hop 1: with the tool result fed back, the model returns the final findings.
        $http->queueJson([
            'content' => [['type' => 'text', 'text' => '{"summary":"done","findings":[]}']],
            'stop_reason' => 'end_turn',
        ]);

        $tool = new RecordingTool('registry_lookup');
        $provider = new AnthropicResearchProvider($http->jsonClient(), 'sk-test');

        $result = $provider->research(
            new ResearchRequest(new Counterparty('Acme', 'PL'), new VerificationReport(), 'sys', 'user'),
            [$tool],
        );

        self::assertSame('done', $result->summary);
        self::assertCount(1, $tool->calls, 'The model-requested tool must have been executed.');
        self::assertSame(['capability' => 'vat_status'], $tool->calls[0]);

        // Two API calls: the tool-use turn, then the final answer with the tool result fed back.
        self::assertCount(2, $http->client->getRequests());
    }
}
