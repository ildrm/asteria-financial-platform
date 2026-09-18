# Performance budgets

Current budgets:

- Local demo quote application handling: p95 below 25 ms excluding WordPress bootstrap/network.
- REST response size below 16 KiB per snapshot quote.
- Instrument search limit capped at 100.
- No high-frequency price storage in WordPress and no unbounded result materialization.

Future frontend modules will lazy-load routes, virtualize large tables, batch streaming updates, and move heavy client calculations to workers. Local modules will publish p50/p95/p99 latency, error rate, freshness, queue lag, cache hit ratio, and saturation through WordPress diagnostics. A benchmark result is recorded only when reproduced by a checked-in harness with hardware/dataset metadata.
