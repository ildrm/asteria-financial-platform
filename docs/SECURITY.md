# Security

## Current controls

REST routes use WordPress authentication plus dedicated capabilities, not role names. Route parameters are allow-list validated and sanitized. Domain validation errors are safe and specific; unexpected exceptions are logged with request IDs and returned as generic errors without traces or database details. Every quote read receives a request ID and audit event. Admin output is contextually escaped. SQL writes use the WordPress database API; dynamic table names derive only from the trusted WordPress prefix. Uninstall always removes plugin capabilities; persistent records are retained unless an administrator explicitly opts into deletion.

The audit table stores a SHA-256 chain across canonical event fields. This detects alteration but is not, alone, immutable storage. Production should replicate audit records to write-once retention and deny application principals UPDATE/DELETE privileges.

## Secrets

No provider secrets are implemented or stored in this milestone. Future adapters must use a vault abstraction and encrypted-at-rest references. A UI must show only secret presence, rotation metadata, and a short non-secret identifier—never the secret value after storage.

## Required hardening before public production

- Edge and application rate limits keyed by organization, user, route, and credential.
- MFA and OIDC/SAML session-policy integration.
- Organization/team RBAC plus resource-level ABAC enforcement.
- SSRF-safe outbound HTTP client with destination allow lists.
- Isolated upload scanning and strict MIME/content validation.
- Automated SAST, dependency, secret, authorization, IDOR, XSS, CSRF, and SQL-injection testing.
- Key rotation, encrypted backups, session revocation, and incident runbooks.

Report vulnerabilities privately to the project maintainers. Do not include credentials or regulated data in a report.
