<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai;

use Gawrys\Counterparty\Ai\Prompt\RiskPromptBuilder;
use Gawrys\Counterparty\Ai\Research\AiResearchProvider;
use Gawrys\Counterparty\Ai\Research\Finding;
use Gawrys\Counterparty\Ai\Research\ResearchResult;
use Gawrys\Counterparty\Ai\Tool\ContextAwareTool;
use Gawrys\Counterparty\Ai\Tool\ResearchContext;
use Gawrys\Counterparty\Ai\Tool\ResearchTool;
use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Enum\RiskLevel;
use Gawrys\Counterparty\Exception\CounterpartyException;
use Gawrys\Counterparty\Report\CheckResult;
use Gawrys\Counterparty\Report\VerificationReport;
use Gawrys\Counterparty\Risk\Evidence;
use Gawrys\Counterparty\Risk\RiskAssessment;
use Gawrys\Counterparty\Risk\RiskStrategy;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

/**
 * AI-backed risk strategy: drop-in replacement for the rule-based default behind the same
 * {@see RiskStrategy} seam. It is ADVISORY ONLY - it consumes the finished report as
 * ground truth and never alters hard pass/fail facts.
 *
 * Guarantees:
 *  - Only grounded findings (with a source URL) become evidence.
 *  - Human review is required on any adverse finding, any ungrounded claim, any
 *    inconclusive/adverse hard fact, or when confidence falls below the threshold.
 *  - Results are cached by counterparty + report + prompt version to bound cost.
 */
final readonly class AiRiskStrategy implements RiskStrategy
{
    /** @var list<ResearchTool> */
    private array $tools;

    private LoggerInterface $logger;

    /**
     * @param iterable<ResearchTool> $tools
     */
    public function __construct(
        private AiResearchProvider $provider,
        private RiskPromptBuilder $promptBuilder,
        iterable $tools,
        private CacheInterface $cache,
        ?LoggerInterface $logger = null,
        private float $reviewThreshold = 0.6,
        private int $cacheTtl = 86400,
    ) {
        $this->tools = \is_array($tools) ? array_values($tools) : iterator_to_array($tools, false);
        $this->logger = $logger ?? new NullLogger();
    }

    public function assess(Counterparty $counterparty, VerificationReport $report): RiskAssessment
    {
        $cacheKey = $this->cacheKey($counterparty, $report);
        /** @var mixed $cached */
        $cached = $this->cache->get($cacheKey);
        if ($cached instanceof RiskAssessment) {
            return $cached;
        }

        $context = new ResearchContext($counterparty, $report);
        $tools = array_map(
            static fn (ResearchTool $tool): ResearchTool => $tool instanceof ContextAwareTool ? $tool->forContext($context) : $tool,
            $this->tools,
        );

        try {
            $result = $this->provider->research(
                $this->promptBuilder->build($counterparty, $report, $tools),
                $tools,
            );
        } catch (CounterpartyException $e) {
            $this->logger->error('AI risk research failed; falling back to human review.', ['exception' => $e]);

            return $this->humanReviewFallback($report);
        }

        $assessment = $this->toAssessment($result, $report);
        $this->cache->set($cacheKey, $assessment, $this->cacheTtl);

        return $assessment;
    }

    private function toAssessment(ResearchResult $result, VerificationReport $report): RiskAssessment
    {
        $score = 0.0;
        $anyAdverse = false;
        $confidence = 0.0;
        $evidence = [];

        foreach ($result->grounded() as $finding) {
            $confidence = max($confidence, $finding->confidence);
            $evidence[] = Evidence::grounded($finding->claim, (string) $finding->sourceUrl, $finding->confidence);
            if ($finding->adverse) {
                $anyAdverse = true;
                $score = max($score, $finding->confidence);
            }
        }

        // Hard facts are ground truth: an adverse deterministic result dominates the score.
        if ($report->hasAdverseFindings()) {
            $anyAdverse = true;
            $score = max($score, 0.9);
        }

        $requiresReview = $anyAdverse
            || $result->hasUngrounded()
            || $report->hasAdverseFindings()
            || $report->hasInconclusive()
            || $confidence < $this->reviewThreshold;

        return new RiskAssessment(
            RiskLevel::fromScore($score),
            $score,
            $result->summary !== '' ? $result->summary : 'AI risk research completed.',
            $requiresReview,
            $evidence,
        );
    }

    private function humanReviewFallback(VerificationReport $report): RiskAssessment
    {
        $score = $report->hasAdverseFindings() ? 0.9 : 0.5;

        return new RiskAssessment(
            RiskLevel::fromScore($score),
            $score,
            'AI research was unavailable; manual review is required.',
            true,
        );
    }

    private function cacheKey(Counterparty $counterparty, VerificationReport $report): string
    {
        $facts = array_map(
            static fn (CheckResult $r): string => $r->source . '=' . $r->status->value,
            $report->results(),
        );

        return \sprintf(
            'cpv_ai_%s_%s_%s',
            $this->promptBuilder->version(),
            $counterparty->fingerprint(),
            hash('sha256', implode('|', $facts)),
        );
    }
}
