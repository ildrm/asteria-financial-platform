# Documentation index

This directory documents the current, self-contained WordPress plugin. Paths are relative to the repository/plugin root; there is no nested `plugin/` directory.

- [Installation](INSTALLATION.md) — supported runtime, installation, activation, retention, and production checklist.
- [Architecture](ARCHITECTURE.md) — modular-monolith boundaries and local runtime decisions.
- [Security](SECURITY.md) — implemented controls and unfinished production gates.
- [Data model](DATA_MODEL.md) — security master, quote lineage, portfolios, orders, and audit records.
- [Providers](PROVIDERS.md) — local deterministic provider and adapter contract.
- [REST API](API.md) — authenticated versioned routes and payload conventions.
- [Quantitative analytics](QUANT.md) — implemented formulas, inputs, units, and limitations.
- [Paper trading](TRADING.md) — local order behavior and regulatory posture.
- [AI roadmap](AI.md), [WebSocket roadmap](WEBSOCKET.md), and [Compliance roadmap](COMPLIANCE.md) — explicitly unimplemented capability boundaries.
- [Performance](PERFORMANCE.md) — budgets and unclaimed benchmarks.
- [Testing](TESTING.md) — executable checks and remaining verification gaps.
- [Development](DEVELOPMENT.md) — code placement and contribution rules.
- [Implementation report](IMPLEMENTATION_REPORT.md) — evidence, counts, and material limitations.
- [Requirements matrix](requirements/feature-matrix.json) — machine-readable source of truth for implementation status.

Documentation must not describe roadmap items as implemented. When the file layout changes, update this index, the root README, installation instructions, tests, and matrix evidence paths in the same change.
