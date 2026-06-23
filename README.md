# gawrys/counterparty-ai

Optional AI-assisted risk-research subsystem for
[`gawrys/counterparty-core`](https://github.com/igorgawrys1/counterparty-core). A separate package because prompts change often and
must not bump core's version.

> ⚠️ **AI is advisory and never decides hard pass/fail.** It consumes the finished
> `VerificationReport` as ground truth, grounds every claim in a tool's source URL (no
> source → inconclusive), and forces human review below a confidence threshold.

## Install

```bash
composer require gawrys/counterparty-ai
```

## What's inside

- `AiRiskStrategy implements RiskStrategy` — drop-in behind the same seam as the rule-based
  default; PSR-16 cached by counterparty + report + prompt version.
- `AiResearchProvider` port + `AbstractAiResearchProvider` (force-JSON / parse / validate /
  retry — malformed output is retried, never trusted).
- Two reference providers over PSR-18 (no SDK): `AnthropicResearchProvider` and
  `OpenAiResearchProvider`. Both use **native tool calling** — the model invokes the tools,
  the provider executes them and feeds results back, looping until it returns the findings
  JSON. Adding another LLM is one `AbstractAiResearchProvider` subclass (implement `complete()`).
- `ResearchTool` interface + reference tools `RegistryTool`, `ReportLookupTool`,
  `WebSearchTool` (each result carries a source URL, so model claims are grounded).
- Versioned, unit-testable `RiskPromptBuilder`.
- Deterministic testing kit shipped in `src`: `FakeAiResearchProvider`, `InMemoryCache`,
  and a `Cassette` loader validated by the production parser.

## Usage

```php
$strategy = new AiRiskStrategy(
    provider: $yourResearchProvider,            // implements AiResearchProvider
    promptBuilder: new RiskPromptBuilder(),
    tools: [new RegistryTool($registries), new ReportLookupTool(), new WebSearchTool($search)],
    cache: $psr16Cache,
    reviewThreshold: 0.6,
);

$verifier = new Verifier($checks, $strategy, $clock);
```

Removing this package never breaks the rule-based default — core has no dependency on it.

MIT licensed.
