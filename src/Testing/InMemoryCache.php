<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Testing;

use Psr\SimpleCache\CacheInterface;

/**
 * Minimal in-memory PSR-16 cache for deterministic tests (TTL is ignored). Ships in src
 * so consumers can exercise the AI strategy's caching without a real backend.
 */
final class InMemoryCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $store = [];

    public int $writes = 0;

    public function get(string $key, mixed $default = null): mixed
    {
        return \array_key_exists($key, $this->store) ? $this->store[$key] : $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->store[$key] = $value;
        ++$this->writes;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->store[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->store = [];

        return true;
    }

    /**
     * @param iterable<mixed, mixed> $keys
     *
     * @return \Generator<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        /** @var mixed $key */
        foreach ($keys as $key) {
            if (!\is_string($key)) {
                throw new InvalidCacheArgument('Cache key must be a string.');
            }
            yield $key => $this->get($key, $default);
        }
    }

    /**
     * @param iterable<mixed, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        /** @var iterable<string, mixed> $typed */
        $typed = $values;
        /** @var mixed $value */
        foreach ($typed as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @param iterable<mixed, mixed> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        /** @var mixed $key */
        foreach ($keys as $key) {
            if (!\is_string($key)) {
                throw new InvalidCacheArgument('Cache key must be a string.');
            }
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->store);
    }
}
