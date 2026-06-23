<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests\Research;

use Gawrys\Counterparty\Ai\Exception\InvalidResearchResult;
use Gawrys\Counterparty\Ai\Research\ResearchResultParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResearchResultParser::class)]
#[CoversClass(InvalidResearchResult::class)]
final class ResearchResultParserTest extends TestCase
{
    private ResearchResultParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ResearchResultParser();
    }

    public function testParsesGroundedAndUngroundedFindings(): void
    {
        $result = $this->parser->parse([
            'summary' => 'Some context.',
            'findings' => [
                ['claim' => 'Listed in adverse media', 'source_url' => 'https://news.test/x', 'confidence' => 0.8, 'adverse' => true],
                ['claim' => 'Rumour without source', 'source_url' => null, 'confidence' => 0.2, 'adverse' => false],
            ],
        ]);

        self::assertCount(2, $result->findings);
        self::assertCount(1, $result->grounded());
        self::assertTrue($result->hasUngrounded());
        self::assertSame('Some context.', $result->summary);
    }

    public function testRejectsMissingClaim(): void
    {
        $this->expectException(InvalidResearchResult::class);

        $this->parser->parse(['findings' => [['confidence' => 0.5]]]);
    }

    public function testRejectsOutOfRangeConfidence(): void
    {
        $this->expectException(InvalidResearchResult::class);

        $this->parser->parse(['findings' => [['claim' => 'x', 'confidence' => 1.4]]]);
    }

    public function testEmptyFindingsAreValid(): void
    {
        $result = $this->parser->parse(['summary' => 'nothing notable', 'findings' => []]);

        self::assertSame([], $result->findings);
    }
}
