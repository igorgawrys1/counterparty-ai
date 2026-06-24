<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests\Research;

use Gawrys\Counterparty\Ai\Exception\InvalidResearchResult;
use Gawrys\Counterparty\Ai\Research\AbstractAiResearchProvider;
use Gawrys\Counterparty\Ai\Research\ResearchRequest;
use Gawrys\Counterparty\Ai\Tests\Fixture\QueuedAiProvider;
use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Report\VerificationReport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractAiResearchProvider::class)]
final class AbstractAiResearchProviderTest extends TestCase
{
    private function request(): ResearchRequest
    {
        return new ResearchRequest(new Counterparty('Acme', 'PL'), new VerificationReport(), 'sys', 'user');
    }

    public function testRetriesAfterMalformedThenSucceeds(): void
    {
        $provider = new QueuedAiProvider([
            '<<not json>>',
            '{"summary":"ok","findings":[{"claim":"c","source_url":"https://x.test","confidence":0.9,"adverse":false}]}',
        ]);

        $result = $provider->research($this->request(), []);

        self::assertCount(1, $result->findings);
    }

    public function testThrowsWhenAllAttemptsAreMalformed(): void
    {
        $provider = new QueuedAiProvider(['oops', 'still bad'], maxAttempts: 2);

        $this->expectException(InvalidResearchResult::class);
        $provider->research($this->request(), []);
    }
}
