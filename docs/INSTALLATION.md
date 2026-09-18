# Installation

## Supported baseline

- WordPress 6.5 or newer on a dedicated HTTPS origin
- PHP 8.2 or newer with JSON, OpenSSL, and PDO/MySQL extensions
- MySQL 8.0 or MariaDB 10.6 or newer
- Composer 2 only when running development checks

## Install

1. Ensure the repository directory is named `asteria-financial-platform`, then ZIP/copy that directory to `wp-content/plugins/asteria-financial-platform`.
2. Activate **Asteria Financial Platform** in WordPress. The bundled autoloader has no external dependencies.
3. Confirm **Asteria → System Health** reports schema `2.0.0` and the local provider healthy.
4. Grant the narrowly scoped `asteria_*` capabilities only to trusted roles.

Activation creates prefixed tables for instruments, identifier mappings, audit events, watchlists, portfolios, positions, and paper orders. Deactivation retains data. Uninstall also retains data unless `asteria_delete_data_on_uninstall` was deliberately enabled beforehand.

## Production checklist

Use TLS, isolated database credentials, encrypted backups, and protected logs. Disable WordPress file editing. WordPress object caching and an external secret manager are optional hardening layers, not runtime dependencies. Do not enable an optional production provider until its data license, display rights, entitlements, quotas, and failure behavior have been reviewed.

Demo mode is synthetic and is enabled by default. It is not a source of tradable prices.
