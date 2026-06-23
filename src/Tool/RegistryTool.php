<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tool;

use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Enum\RegistryCapability;
use Gawrys\Counterparty\Registry\RegistryManager;
use Gawrys\Counterparty\Support\ArrayReader;

/**
 * Lets the model query the capability-routed registries for the counterparty under
 * verification. Bound to that counterparty via {@see self::forContext()}, so the model
 * cannot redirect the lookup to another entity.
 */
final readonly class RegistryTool implements ContextAwareTool
{
    public function __construct(
        private RegistryManager $registries,
        private ?Counterparty $counterparty = null,
    ) {
    }

    public function forContext(ResearchContext $context): static
    {
        return new self($this->registries, $context->counterparty);
    }

    public function name(): string
    {
        return 'registry_lookup';
    }

    public function description(): string
    {
        return 'Look up the counterparty in an official registry for a given capability.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'capability' => [
                    'type' => 'string',
                    'enum' => array_map(static fn (RegistryCapability $c): string => $c->value, RegistryCapability::cases()),
                    'description' => 'Which registry capability to query.',
                ],
            ],
            'required' => ['capability'],
        ];
    }

    public function execute(array $arguments): ToolResult
    {
        if ($this->counterparty === null) {
            return ToolResult::failed('Registry tool has no bound counterparty context.');
        }

        $capabilityValue = ArrayReader::of($arguments)->string('capability');
        $capability = $capabilityValue === null ? null : RegistryCapability::tryFrom($capabilityValue);
        if ($capability === null) {
            return ToolResult::failed('Unknown or missing capability argument.');
        }

        $result = $this->registries->lookup($this->counterparty, $capability);
        if ($result === null) {
            return ToolResult::failed(\sprintf('No registry covers %s for %s.', $capability->value, $this->counterparty->country));
        }

        return new ToolResult($result->found, $result->data, $result->sourceUrl);
    }
}
