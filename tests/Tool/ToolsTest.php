<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests\Tool;

use Gawrys\Counterparty\Ai\Tests\Fixture\FakeRegistryDriver;
use Gawrys\Counterparty\Ai\Tests\Fixture\FakeWebSearchClient;
use Gawrys\Counterparty\Ai\Tool\RegistryTool;
use Gawrys\Counterparty\Ai\Tool\ReportLookupTool;
use Gawrys\Counterparty\Ai\Tool\ResearchContext;
use Gawrys\Counterparty\Ai\Tool\WebSearch\WebSearchResult;
use Gawrys\Counterparty\Ai\Tool\WebSearchTool;
use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Enum\RegistryCapability;
use Gawrys\Counterparty\Registry\RegistryManager;
use Gawrys\Counterparty\Report\CheckResult;
use Gawrys\Counterparty\Report\Source;
use Gawrys\Counterparty\Report\VerificationReport;
use Gawrys\Counterparty\Testing\FrozenClock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RegistryTool::class)]
#[CoversClass(ReportLookupTool::class)]
#[CoversClass(WebSearchTool::class)]
#[CoversClass(ResearchContext::class)]
final class ToolsTest extends TestCase
{
    public function testRegistryToolRoutesForBoundCounterparty(): void
    {
        $manager = new RegistryManager();
        $manager->extend('crbr', static fn (): FakeRegistryDriver => new FakeRegistryDriver(
            [RegistryCapability::BeneficialOwners],
            ['PL'],
        ));

        $counterparty = new Counterparty('Acme', 'PL', nip: '1234567890');
        $tool = (new RegistryTool($manager))->forContext(new ResearchContext($counterparty, new VerificationReport()));

        $result = $tool->execute(['capability' => RegistryCapability::BeneficialOwners->value]);

        self::assertTrue($result->ok);
        self::assertSame('https://registry.example.test', $result->sourceUrl);
    }

    public function testRegistryToolFailsWithoutContext(): void
    {
        $result = (new RegistryTool(new RegistryManager()))->execute(['capability' => 'beneficial_owners']);

        self::assertFalse($result->ok);
    }

    public function testReportLookupToolReturnsGroundTruth(): void
    {
        $report = new VerificationReport(
            CheckResult::fail(Source::SANCTIONS, 'Sanctions', 'Match', (new FrozenClock())->now()),
        );
        $tool = (new ReportLookupTool())->forContext(
            new ResearchContext(new Counterparty('Acme', 'PL'), $report),
        );

        $result = $tool->execute([]);

        self::assertTrue($result->ok);
        self::assertArrayHasKey('results', $result->data);
    }

    public function testWebSearchToolReturnsGroundedHits(): void
    {
        $client = new FakeWebSearchClient([
            new WebSearchResult('Fraud probe', 'https://news.test/1', 'snippet'),
        ]);
        $tool = new WebSearchTool($client);

        $ok = $tool->execute(['query' => 'Acme fraud']);
        $bad = $tool->execute(['query' => '']);

        self::assertTrue($ok->ok);
        self::assertFalse($bad->ok);
        self::assertSame('registry_lookup', (new RegistryTool(new RegistryManager()))->name());
        self::assertNotEmpty($tool->schema());
    }
}
