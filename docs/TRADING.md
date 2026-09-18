# Standalone paper trading

The plugin includes local paper trading that never sends orders outside WordPress. Authorized users can submit market and limit buy/sell orders against synthetic bid/ask prices. Orders use explicit states, idempotency keys, atomic cash/position updates, maximum quantity/notional controls, available-cash checks, and long-position checks. Immediate marketable orders fill locally; non-marketable limit orders remain open in the blotter.

This is simulation only. Installation does not make Asteria a broker, exchange participant, execution venue, or investment adviser. Open-order cancellation is implemented; broker/FIX connectivity, partial fills, order replacement, allocations, settlement, and TCA remain unimplemented and are not required for standalone paper operation.
