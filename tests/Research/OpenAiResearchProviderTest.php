<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests\Research;

use Gawrys\Counterparty\Ai\Research\OpenAiResearchProvider;
use Gawrys\Counterparty\Ai\Research\ResearchRequest;
use Gawrys\Counterparty\Ai\Tests\Fixture\MockHttp;
use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Report\VerificationReport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenAiResearchProvider::class)]
final class OpenAiResearchProviderTest extends TestCase
{
    public function testParsesChatCompletionContentAndSendsBearer(): void
    {
        $http = new MockHttp();
        $http->queueJson([
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => '{"summary":"ok","findings":[{"claim":"c","source_url":"https://x.test","confidence":0.8,"adverse":false}]}']],
            ],
        ]);

        $provider = new OpenAiResearchProvider($http->jsonClient(), 'sk-openai', 'gpt-4o-mini');

        $result = $provider->research(
            new ResearchRequest(new Counterparty('Acme', 'PL'), new VerificationReport(), 'sys', 'user'),
            [],
        );

        self::assertCount(1, $result->grounded());
        $requests = $http->client->getRequests();
        self::assertCount(1, $requests);
        self::assertSame('Bearer sk-openai', $requests[0]->getHeaderLine('Authorization'));
    }
}
