# Architecture

## Decision

WordPress and the installable Asteria plugin form the entire runtime. Identity, capabilities, workspace state, market simulation, portfolios, paper orders, analytics, administration, and versioned APIs execute locally using PHP, browser JavaScript, and prefixed WordPress database tables. There are no required companion processes or network dependencies.

The initial deployment is a modular monolith. Its boundaries are intentionally extractable:

```text
WordPress REST / Admin / CLI
          |
     Application handlers
          |
 Domain models + provider/audit contracts
          |
WordPress persistence | local synthetic providers | local calculation modules
```

Controllers authorize and validate; application handlers coordinate; domain objects enforce invariants; infrastructure implements persistence and external integration. Domain code does not call WordPress or provider SDKs.

## Reliability model

The bundled provider performs no network calls and remains deterministic. Optional future import adapters must normalize errors, declare capability and entitlement state, bound timeouts/retries, and fail without affecting standalone operation.

## Storage allocation

The WordPress database stores security-master, audit, watchlist, portfolio, position, and order records. Deterministic market history is generated locally and is not duplicated in storage. Future local document/search modules must use WordPress tables, uploads, scheduled jobs, and object cache APIs so the plugin remains independently deployable.

## Architecture decisions

- UUIDs are canonical instrument identities; external symbols are time-bounded mappings.
- All timestamps are UTC at module/API boundaries and retain source/ingestion timestamps.
- Audit events are append-oriented and hash chained; production database permissions and retention controls must additionally prevent update/delete.
- API schemas live in `resources/schemas` and evolve compatibly within a major API version.
- Installation enables only the local synthetic paper-trading module. No broker, venue, FIX session, or external execution capability is enabled.
