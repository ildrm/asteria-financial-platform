# Development

PHP code targets 8.2, uses strict types, PSR-4 namespaces, immutable value objects where practical, and domain-specific exceptions. Keep WordPress functions in adapters/controllers; domain code must stay executable without a WordPress bootstrap.

The repository root is also the WordPress plugin root. Runtime code belongs in `app/`, browser assets and schemas in `resources/`, translations in `languages/`, and the only discoverable plugin bootstrap is `asteria-financial-platform.php`. Do not recreate an additional nested `plugin/` directory.

Add behavior in dependency order: contract and invariant, application handler, infrastructure adapter, authorized controller/CLI, tests, docs, and feature-matrix evidence. Never mark a matrix row `VERIFIED` without reproducible evidence for implementation, errors, permissions, tests, UX states, accessibility, security, performance, and docs.

Use UTC at boundaries, decimal rates in calculations, explicit units, bounded collections, stable error codes, and context-aware escaping. Do not commit credentials, licensed datasets, generated vendor content, or `vendor/`.
