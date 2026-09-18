=== Asteria Financial Platform ===
Contributors: ildrm
Tags: finance, portfolio, analytics, paper trading, market data
Requires at least: 6.5
Requires PHP: 8.2
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A self-contained financial intelligence workspace with deterministic synthetic markets, portfolios, risk analytics, and local paper trading.

== Description ==

Asteria runs entirely inside WordPress using PHP, the WordPress database, and bundled browser assets. It does not require a paid market-data provider or companion service for its standalone functionality.

Current functionality includes synthetic cross-asset quotes and history, watchlists, paper portfolios, positions, cash accounting, risk/performance statistics, market and limit paper orders, a responsive workstation, REST APIs, auditing, and financial calculation primitives.

All bundled prices, history, news, and economic events are synthetic and visibly identified. They are not suitable for live trading or investment decisions.

== Installation ==

1. Upload the `asteria-financial-platform` directory to `/wp-content/plugins/`, or install its ZIP through the WordPress Plugins screen.
2. Activate the plugin.
3. Open Asteria in WordPress administration.
4. Confirm Asteria > System Health reports schema 2.0.0 and a healthy local provider.

No Composer command, daemon, hosted API, or external database is required.

== Frequently Asked Questions ==

= Does this connect to a broker? =

No. Orders are local paper simulations and never leave WordPress.

= Is the bundled data live? =

No. It is deterministic synthetic demonstration data and is labeled accordingly.

= Does uninstall delete financial and audit records? =

Not by default. Uninstall removes Asteria capabilities but retains records unless the delete-data option was explicitly enabled beforehand.

== Changelog ==

= 0.2.0 =

* Consolidated the complete runtime into a standard WordPress plugin root.
* Added the standalone workstation, local history, portfolios, risk analytics, and transactional paper trading.
* Added bundled autoloading so runtime installation has no Composer dependency.
