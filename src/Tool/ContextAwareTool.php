<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Ai\Tool;

/**
 * A tool that must be bound to the counterparty/report under verification before use.
 * The strategy rebinds these immutably for each run.
 */
interface ContextAwareTool extends ResearchTool
{
    public function forContext(ResearchContext $context): static;
}
