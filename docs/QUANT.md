# Quantitative analytics

The current standalone milestone provides audited calculation primitives, not a complete quant workbench.

- Fixed-coupon bullet bonds: dirty/clean price, accrued interest, current yield, Macaulay/modified duration, convexity, and DV01 on regular coupon periods.
- European options: Black-Scholes-Merton price and delta, gamma, annual theta, vega per volatility point, rho per rate point, intrinsic value, and time value with continuous dividend yield.
- FX: covered-interest-parity outright forwards and forward points using simple rates.
- Portfolio analytics: compounded total return, annualized volatility, Sharpe ratio, maximum drawdown, and one-day historical 95% VaR/expected shortfall from local synthetic daily returns.

Inputs use decimal rates. Tests use deterministic published/golden cases and tolerance comparisons. Calendars, irregular stubs, day-count conventions, callable/putable models, curves, American exercise, volatility surfaces, factor risk, stress testing, backtesting, and optimization remain separate matrix items; the implemented primitives must not be misrepresented as those features.
