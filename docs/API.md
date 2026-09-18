# REST API

Base namespace: `/wp-json/asteria/v1`. Browser requests use the normal WordPress REST nonce; application passwords are suitable only where organizational policy permits them. Quote, instrument, history, and watchlist routes require `asteria_view_market_data`; the workspace requires `asteria_access_platform`; paper-order routes require `asteria_paper_trade`.

## Snapshot quote

`GET /market-data/quotes/{symbol}?provider=demo`

Returns `{data, meta.request_id}`. The `X-Request-ID` response header correlates logs and audit events. The payload follows `resources/schemas/quote.schema.json`. Errors use a stable WordPress error code and HTTP 404 or 503; internal traces are not returned.

## Standalone workspace

`GET /workspace` returns local watchlist quotes/history, the current user's paper portfolio, positions, performance/risk metrics, recent orders, synthetic news, and a synthetic economic calendar. `GET /market-data/history/{symbol}` returns 2–730 deterministic daily bars.

`POST /paper-orders` places a local market or limit paper order. It requires an `Idempotency-Key` header and the `asteria_paper_trade` capability. `DELETE /paper-orders/{id}` cancels an owned open paper order. `POST` and `DELETE /watchlist/{symbol}` manage the user's local watchlist.

## Instrument search

`GET /instruments?q=gold&limit=20`

Returns canonical identity fields and explicitly marks demo instruments synthetic. Limits are bounded to 1–100.

The API is versioned. Breaking changes require a new major namespace. All future mutating routes require capability checks, idempotency keys where retries are possible, and append-oriented audit events.
