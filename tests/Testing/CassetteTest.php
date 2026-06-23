<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests\Testing;

use Gawrys\Counterparty\Ai\Testing\Cassette;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Cassette::class)]
final class CassetteTest extends TestCase
{
    public function testLoadsAndValidatesRecordedResponseFromFile(): void
    {
        $result = (new Cassette())->fromFile(__DIR__ . '/../cassettes/adverse-media.json');

        self::assertCount(1, $result->grounded());
        self::assertSame('One adverse-media hit located.', $result->summary);
    }

    public function testInvalidCassetteIsRejectedByTheSameValidatorAsProduction(): void
    {
        $this->expectException(\Gawrys\Counterparty\Ai\Exception\InvalidResearchResult::class);

        (new Cassette())->fromJson('{"findings":[{"confidence":2.0}]}');
    }
}
