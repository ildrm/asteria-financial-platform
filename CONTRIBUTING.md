# Contributing

The repository root is the WordPress plugin root. Keep the bootstrap at `asteria-financial-platform.php`; place namespaced PHP under `app/`, runtime browser assets and schemas under `resources/`, translations under `languages/`, tests under `tests/`, and engineering documentation under `docs/`.

Do not create a nested `plugin/` directory. Do not commit credentials, licensed market data, generated logs, ZIP packages, or `vendor/`.

Before submitting a change, run:

```bash
composer validate --strict
composer test
composer lint
node --check resources/js/workspace.js
```

Financial calculation changes require a deterministic golden test with an explicit tolerance. Provider changes must document provenance, licensing, entitlements, quotas, timestamps, and failure behavior. Security-sensitive changes require authorization and failure-mode tests. Update `docs/requirements/feature-matrix.json` only when its evidence paths and statuses are supported by the repository.
