<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Testing;

use Psr\SimpleCache\InvalidArgumentException;

/**
 * Thrown by {@see InMemoryCache} for invalid (non-string) cache keys, as required by PSR-16.
 */
final class InvalidCacheArgument extends \InvalidArgumentException implements InvalidArgumentException
{
}
