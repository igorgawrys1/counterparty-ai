<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests\Prompt;

use Gawrys\Counterparty\Ai\Prompt\RiskPromptBuilder;
use Gawrys\Counterparty\Ai\Tests\Fixture\FakeWebSearchClient;
use Gawrys\Counterparty\Ai\Tool\WebSearchTool;
use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Report\CheckResult;
use Gawrys\Counterparty\Report\Source;
use Gawrys\Counterparty\Report\VerificationReport;
use Gawrys\Counterparty\Testing\FrozenClock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RiskPromptBuilder::class)]
final class RiskPromptBuilderTest extends TestCase
{
    public function testBuildsVersionedPromptWithToolsAndFacts(): void
    {
        $builder = new RiskPromptBuilder();
        $client = new FakeWebSearchClient();

        $report = new VerificationReport(
            CheckResult::fail(Source::SANCTIONS, 'Sanctions', 'Match found', (new FrozenClock())->now()),
        );
        $counterparty = new Counterparty('Acme', 'PL', nip: '1234567890');

        $request = $builder->build($counterparty, $report, [new WebSearchTool($client)]);

        self::assertStringContainsString('prompt v1', $request->systemPrompt);
        self::assertStringContainsString('web_search', $request->systemPrompt);
        self::assertStringContainsString('ADVISORY', $request->systemPrompt);
        self::assertStringContainsString('Match found', $request->userPrompt);
        self::assertStringContainsString('NIP=1234567890', $request->userPrompt);
        self::assertSame('v1', $builder->version());
    }

    public function testUserPromptHandlesEmptyReport(): void
    {
        $request = (new RiskPromptBuilder())->build(new Counterparty('Acme', 'DE'), new VerificationReport(), []);

        self::assertStringContainsString('no deterministic checks', $request->userPrompt);
    }
}
