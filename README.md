# Counterparty AI

[![CI](https://github.com/igorgawrys1/counterparty-ai/actions/workflows/ci.yml/badge.svg)](https://github.com/igorgawrys1/counterparty-ai/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/php-8.2%20|%208.3%20|%208.4-777bb4.svg)](https://www.php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-max-brightgreen.svg)](https://phpstan.org/)
[![Psalm](https://img.shields.io/badge/Psalm-level%201-brightgreen.svg)](https://psalm.dev/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Optional **AI-assisted risk research** for the
[Counterparty Verification](https://github.com/igorgawrys1/counterparty-core) toolkit. It
plugs into the same `RiskStrategy` seam as the rule-based default and adds qualitative,
**advisory** context using an LLM with native tool use.

> ⚠️ **The AI never decides hard pass/fail.** It consumes the finished verification report
> as ground truth, grounds every claim in a tool's source URL (no source → inconclusive),
> and forces human review below a confidence threshold. Output is advisory only.

A separate package on purpose: prompts change often and must not bump the core's version.

## Features

- **`AiRiskStrategy`** — a drop-in `RiskStrategy`; PSR-16 cached by counterparty + report +
  prompt version to bound cost.
- **Native tool use (function calling)** — the model invokes `registry_lookup`,
  `web_search` and `verification_report`; the provider executes them and feeds results back,
  looping until the findings JSON is returned.
- **Two reference providers over PSR-18, no SDK** — `AnthropicResearchProvider`,
  `OpenAiResearchProvider`. Adding another LLM is one `AbstractAiResearchProvider` subclass.
- **Structured output, validated** — force JSON, parse, validate, retry; malformed output is
  never trusted.
- **Deterministic test kit** — `FakeAiResearchProvider`, recorded cassettes and an in-memory
  PSR-16 cache, so tests run offline.

## Installation

```bash
composer require gawrys/counterparty-ai
```

## Usage

```php
use Gawrys\Counterparty\Ai\AiRiskStrategy;
use Gawrys\Counterparty\Ai\Prompt\RiskPromptBuilder;
use Gawrys\Counterparty\Ai\Research\AnthropicResearchProvider; // or OpenAiResearchProvider
use Gawrys\Counterparty\Ai\Tool\{RegistryTool, ReportLookupTool};

$strategy = new AiRiskStrategy(
    provider: new AnthropicResearchProvider($http, $apiKey),
    promptBuilder: new RiskPromptBuilder(),
    tools: [new RegistryTool($registries), new ReportLookupTool()],
    cache: $psr16Cache,
    reviewThreshold: 0.6,
);

$verifier = new Verifier($checks, $strategy, $clock);
```

Switching LLM is a one-line change (`OpenAiResearchProvider`), or implement
`AbstractAiResearchProvider::complete()` for any other backend. Removing this package never
breaks the rule-based default — the core has no dependency on it.

See the **[documentation](https://igorgawrys1.github.io/counterparty-verification/ai/)** for
prompts, grounding rules and writing custom tools.

## Testing

```bash
composer check
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing & Security

Pull requests welcome. Report security issues privately — see [SECURITY.md](SECURITY.md).

## Credits

- [Igor Gawrys](https://github.com/igorgawrys1)

## License

The MIT License (MIT). See [LICENSE](LICENSE).
