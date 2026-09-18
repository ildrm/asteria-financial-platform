# Provider adapters

`MarketDataProviderInterface` requires metadata/capability discovery, authentication status, health, entitlement checks, instrument lookup, and quote retrieval. Metadata includes provenance, licensing, timeout, retry, and quota information.

Production adapters must additionally implement bounded exponential retry with jitter for retryable operations, circuit breaking, normalized typed failures, quota telemetry, stale-data classification, provider timestamps, and exchange display/non-display policy. Domain or controller code must never branch on a vendor name.

## Demo provider

The bundled `demo` provider covers a synthetic equity, government benchmark, FX pair, commodity, and crypto pair. Values are fixed and timestamps come from an injected clock, making tests reproducible. Every response says `synthetic: true`, quality `INDICATIVE`, and includes licensing/provenance metadata.

## Adding an adapter

1. Implement the relevant contract under `app/Providers/<Provider>/`.
2. Keep SDK types inside that adapter.
3. Register it in the provider registry through configuration/DI.
4. Add contract tests for auth failure, timeout, quota exhaustion, malformed data, stale data, and entitlement denial.
5. Document required credentials, datasets, licensing/display restrictions, rate limits, and operational alerts.
