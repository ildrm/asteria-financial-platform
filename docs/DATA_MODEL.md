# Data model

## Security master

`asteria_instruments` stores the canonical UUID, canonical symbol, name, type, ISO currency, optional MIC, lifecycle status, and validity timestamps. `asteria_instrument_identifiers` stores extensible, provider-aware mappings (ticker, ISIN, CUSIP, SEDOL, FIGI, LEI, MIC, or vendor ID) with validity intervals. Identifier values are not treated as permanent entity identities.

## Quote lineage

Every quote carries provider, source, observed timestamp, ingestion timestamp, currency, quality, licensing text, and a synthetic flag. The quote object rejects negative prices/volume, crossed bid/ask markets, and inconsistent high/low/last bounds.

## Audit

`asteria_audit_events` contains event type, actor, request ID, JSON context, microsecond UTC time, previous hash, and event hash. Context must exclude secrets and unnecessary personal data.

Synthetic daily history is generated deterministically inside the plugin, avoiding high-frequency table growth. Portfolio, position, watchlist, and explicit order-state records use dedicated indexed WordPress tables.

## Portfolio and paper orders

`asteria_portfolios` stores the owning WordPress user, display name, base currency, and paper cash. `asteria_positions` stores quantity and average cost keyed by portfolio and symbol. `asteria_orders` stores UUID, owner, portfolio, symbol, explicit side/type/status, prices, quantity, rejection reason, timestamps, and a user-scoped idempotency key. `asteria_watchlist_items` is keyed by WordPress user and symbol. Application queries scope private records to the current user.
