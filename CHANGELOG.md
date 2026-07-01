# Changelog

All notable changes are documented here, following [Keep a Changelog](https://keepachangelog.com/)
and [Semantic Versioning](https://semver.org/).

## [0.1.2]

### Changed
- Maintainer contact e-mail updated to igor@gawrys.me (composer `authors`, SECURITY.md).

## [0.1.1]

### Changed
- Published on Packagist; `gawrys/counterparty-core` now resolves from Packagist (dropped the
  `repositories: vcs` entry and the CI `COMPOSER_AUTH` step).
- Widen `ToolResult` data and the provider's `objectList()` to `array<array-key, mixed>` to
  match core's JSON typing.

## [0.1.0]

### Added
- Advisory `AiRiskStrategy` (implements core `RiskStrategy`); never decides hard pass/fail.
- `AiResearchProvider` port + `AbstractAiResearchProvider` (force-JSON / parse / validate /
  retry) with **native tool use** (function calling).
- Reference providers over PSR-18 (no SDK): `AnthropicResearchProvider`, `OpenAiResearchProvider`.
- Research tools: `RegistryTool`, `ReportLookupTool`, `WebSearchTool` (each result carries a
  source URL so claims are grounded).
- Versioned `RiskPromptBuilder`; deterministic testing kit (fake provider, cassettes,
  in-memory cache).
