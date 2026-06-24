<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tests\Fixture;

use Gawrys\Counterparty\Http\JsonHttpClient;
use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * Wires a {@see JsonHttpClient} over an in-memory PSR-18 mock so adapter tests never touch
 * the network. Responses are returned in the order they are queued.
 */
final class MockHttp
{
    public readonly Client $client;
    public readonly Psr17Factory $factory;

    public function __construct()
    {
        $this->client = new Client();
        $this->factory = new Psr17Factory();
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public function queueJson(array $data, int $status = 200): void
    {
        $body = json_encode($data, \JSON_THROW_ON_ERROR);
        $this->client->addResponse(
            $this->factory->createResponse($status)->withBody($this->factory->createStream($body)),
        );
    }

    public function queueRaw(string $body, int $status = 200): void
    {
        $this->client->addResponse(
            $this->factory->createResponse($status)->withBody($this->factory->createStream($body)),
        );
    }

    public function jsonClient(): JsonHttpClient
    {
        return new JsonHttpClient($this->client, $this->factory, $this->factory);
    }
}
