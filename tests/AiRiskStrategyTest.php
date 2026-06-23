<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests;

use Gawrys\Counterparty\Ai\AiRiskStrategy;
use Gawrys\Counterparty\Ai\Prompt\RiskPromptBuilder;
use Gawrys\Counterparty\Ai\Research\AiResearchProvider;
use Gawrys\Counterparty\Ai\Research\Finding;
use Gawrys\Counterparty\Ai\Research\ResearchResult;
use Gawrys\Counterparty\Ai\Testing\FakeAiResearchProvider;
use Gawrys\Counterparty\Ai\Testing\InMemoryCache;
use Gawrys\Counterparty\Ai\Tests\Fixture\ThrowingAiProvider;
use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Enum\RiskLevel;
use Gawrys\Counterparty\Report\CheckResult;
use Gawrys\Counterparty\Report\Source;
use Gawrys\Counterparty\Report\VerificationReport;
use Gawrys\Counterparty\Testing\FrozenClock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AiRiskStrategy::class)]
#[CoversClass(ResearchResult::class)]
#[CoversClass(Finding::class)]
#[CoversClass(FakeAiResearchProvider::class)]
#[CoversClass(InMemoryCache::class)]
final class AiRiskStrategyTest extends TestCase
{
    private Counterparty $counterparty;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->counterparty = new Counterparty('Acme', 'PL', nip: '1234567890');
        $this->now = (new FrozenClock())->now();
    }

    private function strategy(AiResearchProvider $provider, InMemoryCache $cache, float $threshold = 0.6): AiRiskStrategy
    {
        return new AiRiskStrategy($provider, new RiskPromptBuilder(), [], $cache, null, $threshold);
    }

    public function testGroundedAdverseFindingRaisesRiskAndReview(): void
    {
        $provider = new FakeAiResearchProvider();
        $provider->record($this->counterparty->fingerprint(), new ResearchResult([
            new Finding('Subject of fraud investigation', 'https://news.test/case', 0.75, true),
        ], 'Adverse media found.'));

        $assessment = $this->strategy($provider, new InMemoryCache())->assess($this->counterparty, new VerificationReport());

        self::assertTrue($assessment->level->isAtLeast(RiskLevel::High));
        self::assertTrue($assessment->requiresHumanReview());
        self::assertCount(1, $assessment->groundedEvidence());
    }

    public function testCleanHighConfidenceResearchNeedsNoReview(): void
    {
        $provider = new FakeAiResearchProvider();
        $provider->record($this->counterparty->fingerprint(), new ResearchResult([
            new Finding('Established, reputable operator', 'https://registry.test/ok', 0.9, false),
        ], 'No concerns.'));

        $report = new VerificationReport(
            CheckResult::pass(Source::WHITE_LIST, 'White List', 'Active', $this->now, ['bankAccountAssigned' => true]),
        );

        $assessment = $this->strategy($provider, new InMemoryCache())->assess($this->counterparty, $report);

        self::assertSame(RiskLevel::Low, $assessment->level);
        self::assertFalse($assessment->requiresHumanReview());
    }

    public function testUngroundedClaimForcesReview(): void
    {
        $provider = new FakeAiResearchProvider();
        $provider->record($this->counterparty->fingerprint(), new ResearchResult([
            new Finding('Heard something concerning', null, 0.4, false),
        ]));

        $assessment = $this->strategy($provider, new InMemoryCache())->assess($this->counterparty, new VerificationReport());

        self::assertTrue($assessment->requiresHumanReview());
        self::assertCount(0, $assessment->groundedEvidence());
    }

    public function testHardSanctionFactDominatesEvenWithoutAiFindings(): void
    {
        $provider = new FakeAiResearchProvider(new ResearchResult([], 'Nothing notable online.'));
        $report = new VerificationReport(
            CheckResult::fail(Source::SANCTIONS, 'Sanctions', 'Match found', $this->now),
        );

        $assessment = $this->strategy($provider, new InMemoryCache())->assess($this->counterparty, $report);

        self::assertSame(RiskLevel::Critical, $assessment->level);
        self::assertTrue($assessment->requiresHumanReview());
    }

    public function testResultsAreCachedByCounterpartyAndReport(): void
    {
        $provider = new FakeAiResearchProvider(new ResearchResult([], 'cached'));
        $cache = new InMemoryCache();
        $strategy = $this->strategy($provider, $cache);

        $strategy->assess($this->counterparty, new VerificationReport());
        $strategy->assess($this->counterparty, new VerificationReport());

        self::assertSame(1, $provider->calls, 'The provider must be invoked once for an identical request.');
        self::assertSame(1, $cache->writes);
    }

    public function testProviderFailureFallsBackToHumanReview(): void
    {
        $assessment = $this->strategy(new ThrowingAiProvider(), new InMemoryCache())
            ->assess($this->counterparty, new VerificationReport());

        self::assertTrue($assessment->requiresHumanReview());
        self::assertStringContainsString('manual review', $assessment->summary);
    }
}
