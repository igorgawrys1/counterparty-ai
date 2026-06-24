<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests\Fixture;

/**
 * A tiny mutable FIFO of strings, so a readonly provider fixture can advance through
 * queued responses without itself being mutable.
 */
final class StringQueue
{
    /** @var list<string> */
    private array $items;

    private int $position = 0;

    /**
     * @param list<string> $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function next(string $fallback = '{}'): string
    {
        $value = $this->items[$this->position] ?? $fallback;
        ++$this->position;

        return $value;
    }
}
