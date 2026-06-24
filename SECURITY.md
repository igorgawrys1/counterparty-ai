# Security Policy

## Reporting a vulnerability

Please **do not** open a public issue for security problems. Instead, report privately via
GitHub Security Advisories ("Report a vulnerability" on the repository's Security tab), or
email the maintainer at **igor@fikg.pl**. You will receive an acknowledgement as soon as
possible.

## Scope & handling of sensitive data

This is a due-diligence toolkit that processes third-party identifiers (NIP, VAT, IBAN) and
calls external registries/LLMs. When reporting or contributing, keep in mind:

- **Never commit secrets** (API keys, tokens). Configuration is read from the environment;
  `.env` is git-ignored and `.env.example` contains placeholders only.
- Some credentials embed personal data (e.g. a CEIDG bearer token contains a PESEL) - treat
  them as secrets and rotate any that are exposed.
- The library persists only a due-diligence proof identifier + timestamp in `CheckResult`;
  it does not silently store PII. Please preserve that property in contributions.

## Supported versions

While pre-1.0, only the latest `0.x` release receives security fixes.
