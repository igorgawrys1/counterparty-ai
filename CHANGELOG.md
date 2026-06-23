# Changelog

All notable changes are documented here, following [Keep a Changelog](https://keepachangelog.com/)
and [Semantic Versioning](https://semver.org/).

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
