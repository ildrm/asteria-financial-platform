# Asteria Financial Platform

Asteria is a self-contained financial intelligence and paper-trading workspace delivered as a standard WordPress plugin. Its executable PHP code, browser assets, schemas, deterministic synthetic market data, persistence, and calculations all live in this repository root. It does not require a companion service, paid data feed, Redis, or Composer at runtime.

> Asteria is informational software. Synthetic data is not tradable market data, and analytics are not investment advice. Paper orders never leave the WordPress installation.

## Implemented capabilities

- Responsive dark/light financial workspace with accessible navigation and RTL-aware styling.
- Deterministic synthetic quotes and daily OHLCV history across equity, rates, FX, commodity, and crypto examples.
- Per-user watchlists, paper portfolios, positions, cash accounting, and an order blotter.
- Transactional market and limit paper orders with ownership, quantity, notional, cash, position, and idempotency controls.
- Portfolio total return, volatility, Sharpe ratio, drawdown, historical VaR, and expected shortfall.
- Fixed-coupon bond, Black–Scholes–Merton option, and covered-interest-parity FX-forward calculations.
- Capability-protected WordPress REST APIs, local administration/health UI, WP-CLI provider health, migrations, and hash-chained audit events.
- Dependency-free bundled autoloading and deterministic tests.

The broader product roadmap is not represented as complete. See the [requirements matrix](docs/requirements/feature-matrix.json) and [implementation report](docs/IMPLEMENTATION_REPORT.md) for exact status and limitations.

## Requirements

- WordPress 6.5 or newer
- PHP 8.2 or newer
- MySQL 8.0 or MariaDB 10.6 or newer
- HTTPS for production installations

Composer and Node.js are development tools only; neither is needed to activate or use the plugin.

## Installation

1. Ensure this directory is named `asteria-financial-platform`.
2. Copy it to `wp-content/plugins/`, or ZIP the directory and upload it through **Plugins → Add New → Upload Plugin**.
3. Activate **Asteria Financial Platform**.
4. Open **Asteria** in WordPress administration.
5. Verify **Asteria → System Health** reports schema `2.0.0` and a healthy local provider.

Activation creates dedicated prefixed tables and grants Asteria capabilities to the administrator role. Deactivation retains data. Uninstall removes capabilities; stored data is retained unless the `asteria_delete_data_on_uninstall` option was explicitly enabled before uninstalling.

See [Installation](docs/INSTALLATION.md) for production guidance.

## Repository structure

```text
asteria-financial-platform.php  WordPress plugin bootstrap
autoload.php                    bundled PSR-4 runtime loader
uninstall.php                   guarded uninstall routine
app/                            domain, application, HTTP, admin, and infrastructure code
resources/css/                  workstation styles
resources/js/                   dependency-free workstation client
resources/schemas/              versioned JSON schemas
languages/                      WordPress translation catalogs
docs/                           architecture and operating documentation
tests/                          deterministic unit/golden test harness
tools/                          development utilities
```

The root layout is the plugin layout—there is no additional `plugin/` directory.

## Development

```bash
composer validate --strict
composer test
composer lint
node --check resources/js/workspace.js
```

The bundled [`autoload.php`](autoload.php) is what makes normal ZIP installations independent of Composer. Composer’s generated autoloader may be used locally but `vendor/` is not part of the source distribution.

## Documentation

- [Architecture](docs/ARCHITECTURE.md)
- [Security](docs/SECURITY.md)
- [Data model](docs/DATA_MODEL.md)
- [REST API](docs/API.md)
- [Providers](docs/PROVIDERS.md)
- [Quantitative calculations](docs/QUANT.md)
- [Paper trading](docs/TRADING.md)
- [Testing](docs/TESTING.md)
- [Performance](docs/PERFORMANCE.md)
- [Development](docs/DEVELOPMENT.md)

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
