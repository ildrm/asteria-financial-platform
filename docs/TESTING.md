# Testing

Run from the plugin repository root:

```bash
composer test
composer lint
```

The dependency-free test harness exercises deterministic provider behavior, provenance, entitlement discovery, quote invariants, missing instruments, adapter registration, local historical data, portfolio analytics, fixed-income golden calculations, Black-Scholes golden calculations, covered-interest-parity FX forwards, installable plugin-root structure, documentation links, and traceability integrity. It exits non-zero on failure and is suitable for a minimal CI gate. Browser JavaScript is checked with `node --check` during verification.

WordPress integration tests, REST authorization/nonce tests, migration tests on supported databases, browser E2E, accessibility, load/streaming, dependency scanning, and adversarial security suites remain required before a production release and are not represented as passing.
