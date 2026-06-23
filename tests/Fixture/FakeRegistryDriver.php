<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests\Fixture;

use Gawrys\Counterparty\Enum\RegistryCapability;
use Gawrys\Counterparty\Registry\AbstractRegistryDriver;
use Gawrys\Counterparty\Registry\LookupRequest;
use Gawrys\Counterparty\Registry\LookupResult;

final readonly class FakeRegistryDriver extends AbstractRegistryDriver
{
    /**
     * @param list<RegistryCapability> $capabilities
     * @param list<string> $countries
     */
    public function __construct(
        private readonly array $capabilities,
        private readonly array $countries,
        private readonly bool $found = true,
    ) {
    }

    public function capabilities(): array
    {
        return $this->capabilities;
    }

    public function countries(): array
    {
        return $this->countries;
    }

    public function lookup(LookupRequest $request): LookupResult
    {
        if (!$this->found) {
            return LookupResult::notFound('https://registry.example.test');
        }

        return LookupResult::found(
            ['country' => $request->counterparty->country, 'capability' => $request->capability->value],
            'proof-' . $request->counterparty->fingerprint(),
            'https://registry.example.test',
        );
    }
}
