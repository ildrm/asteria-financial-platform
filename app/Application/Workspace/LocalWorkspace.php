<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Application\Workspace;

use Asteria\FinancialPlatform\Application\MarketData\DemoHistoricalSeries;
use Asteria\FinancialPlatform\Contracts\Provider\MarketDataProviderInterface;
use Asteria\FinancialPlatform\Domain\Analytics\PortfolioAnalytics;
use Asteria\FinancialPlatform\Support\Clock;
use RuntimeException;
use wpdb;

final readonly class LocalWorkspace
{
    private const DEFAULT_SYMBOLS = ['ASTR', 'US10Y', 'EURUSD', 'XAUUSD', 'BTCUSD'];

    public function __construct(
        private wpdb $database,
        private MarketDataProviderInterface $provider,
        private DemoHistoricalSeries $history,
        private PortfolioAnalytics $analytics,
        private Clock $clock,
    ) {
    }

    /** @return array<string, mixed> */
    public function snapshot(int $userId): array
    {
        $portfolio = $this->portfolio($userId);
        $watchlist = $this->watchlist($userId);
        $quotes = [];
        foreach ($watchlist as $symbol) {
            $quotes[] = $this->provider->quote($symbol)->jsonSerialize();
        }

        $positions = $this->positions((int) $portfolio['id']);
        $marketValue = 0.0;
        $costBasis = 0.0;
        foreach ($positions as &$position) {
            $quote = $this->provider->quote((string) $position['symbol']);
            $position['last'] = $quote->last;
            $position['market_value'] = (float) $position['quantity'] * $quote->last;
            $position['cost_basis'] = (float) $position['quantity'] * (float) $position['average_cost'];
            $position['unrealized_pnl'] = $position['market_value'] - $position['cost_basis'];
            $marketValue += $position['market_value'];
            $costBasis += $position['cost_basis'];
        }
        unset($position);

        $cash = (float) $portfolio['cash'];
        $equity = $cash + $marketValue;
        foreach ($positions as &$position) {
            $position['weight'] = $equity === 0.0 ? 0.0 : $position['market_value'] / $equity;
        }
        unset($position);

        $returns = $this->portfolioReturns($positions);
        $metrics = $this->analytics->calculate($returns);
        $risk = [
            'total_return' => $metrics->totalReturn,
            'annualized_volatility' => $metrics->annualizedVolatility,
            'sharpe_ratio' => $metrics->sharpeRatio,
            'max_drawdown' => $metrics->maxDrawdown,
            'historical_var_95' => $metrics->historicalVar95,
            'expected_shortfall_95' => $metrics->expectedShortfall95,
            'methodology' => 'Synthetic daily close-to-close returns; 252-day annualization; historical 95% one-day VaR/ES',
        ];

        return [
            'mode' => 'standalone_demo',
            'as_of' => $this->clock->now()->format(DATE_ATOM),
            'market' => ['quotes' => $quotes, 'history' => $this->history->daily($watchlist[0] ?? 'ASTR', 90)],
            'portfolio' => [
                'id' => (int) $portfolio['id'],
                'name' => (string) $portfolio['name'],
                'base_currency' => (string) $portfolio['base_currency'],
                'cash' => $cash,
                'market_value' => $marketValue,
                'equity' => $equity,
                'cost_basis' => $costBasis,
                'unrealized_pnl' => $marketValue - $costBasis,
                'positions' => $positions,
                'risk' => $risk,
            ],
            'orders' => $this->orders($userId),
            'news' => $this->localNews(),
            'economics' => $this->localEconomics(),
        ];
    }

    /** @return list<string> */
    public function watchlist(int $userId): array
    {
        $table = $this->database->prefix . 'asteria_watchlist_items';
        $symbols = $this->database->get_col($this->database->prepare("SELECT symbol FROM {$table} WHERE user_id = %d ORDER BY created_at ASC", $userId));
        if ($symbols !== []) {
            return array_map('strval', $symbols);
        }
        foreach (self::DEFAULT_SYMBOLS as $symbol) {
            $this->addWatchlist($userId, $symbol);
        }
        return self::DEFAULT_SYMBOLS;
    }

    public function addWatchlist(int $userId, string $symbol): void
    {
        $canonical = strtoupper($symbol);
        $this->provider->quote($canonical);
        $this->database->query($this->database->prepare(
            "INSERT IGNORE INTO {$this->database->prefix}asteria_watchlist_items (user_id, symbol, created_at) VALUES (%d, %s, %s)",
            $userId,
            $canonical,
            $this->clock->now()->format('Y-m-d H:i:s.u'),
        ));
    }

    public function removeWatchlist(int $userId, string $symbol): void
    {
        $table = $this->database->prefix . 'asteria_watchlist_items';
        $count = (int) $this->database->get_var($this->database->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = %d", $userId));
        if ($count <= 1) {
            throw new \Asteria\FinancialPlatform\Domain\Exception\DomainException('A watchlist must retain at least one instrument.');
        }
        $this->database->delete($table, ['user_id' => $userId, 'symbol' => strtoupper($symbol)], ['%d', '%s']);
    }

    /** @return array<string, mixed> */
    private function portfolio(int $userId): array
    {
        $table = $this->database->prefix . 'asteria_portfolios';
        $portfolio = $this->database->get_row($this->database->prepare("SELECT * FROM {$table} WHERE user_id = %d ORDER BY id ASC LIMIT 1", $userId), ARRAY_A);
        if (is_array($portfolio)) {
            return $portfolio;
        }
        $now = $this->clock->now()->format('Y-m-d H:i:s.u');
        $created = $this->database->insert($table, ['user_id' => $userId, 'name' => 'Asteria Paper Portfolio', 'base_currency' => 'USD', 'cash' => 100000.0, 'created_at' => $now, 'updated_at' => $now], ['%d', '%s', '%s', '%f', '%s', '%s']);
        if ($created === false) {
            throw new RuntimeException('The local portfolio could not be created.');
        }
        return ['id' => (int) $this->database->insert_id, 'user_id' => $userId, 'name' => 'Asteria Paper Portfolio', 'base_currency' => 'USD', 'cash' => 100000.0];
    }

    /** @return list<array<string, mixed>> */
    private function positions(int $portfolioId): array
    {
        $table = $this->database->prefix . 'asteria_positions';
        $rows = $this->database->get_results($this->database->prepare("SELECT symbol, quantity, average_cost FROM {$table} WHERE portfolio_id = %d ORDER BY symbol", $portfolioId), ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    /** @return list<array<string, mixed>> */
    private function orders(int $userId): array
    {
        $table = $this->database->prefix . 'asteria_orders';
        $rows = $this->database->get_results($this->database->prepare("SELECT id, portfolio_id, symbol, side, order_type, quantity, limit_price, fill_price, status, rejection_reason, created_at FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 50", $userId), ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    /** @param list<array<string, mixed>> $positions @return list<float> */
    private function portfolioReturns(array $positions): array
    {
        if ($positions === []) {
            return array_fill(0, 252, 0.0);
        }
        $series = array_fill(0, 252, 0.0);
        foreach ($positions as $position) {
            $weight = (float) $position['weight'];
            foreach ($this->history->returns((string) $position['symbol'], 252) as $index => $return) {
                $series[$index] += $weight * $return;
            }
        }
        return $series;
    }

    /** @return list<array<string, string>> */
    private function localNews(): array
    {
        return [
            ['headline' => 'Synthetic markets open with broad cross-asset participation', 'topic' => 'Markets', 'source' => 'Asteria Local Demo', 'time' => $this->clock->now()->modify('-12 minutes')->format(DATE_ATOM)],
            ['headline' => 'Demo central bank maintains its illustrative policy rate', 'topic' => 'Economics', 'source' => 'Asteria Local Demo', 'time' => $this->clock->now()->modify('-48 minutes')->format(DATE_ATOM)],
            ['headline' => 'Asteria Systems publishes fictional quarterly operating update', 'topic' => 'Equities', 'source' => 'Asteria Local Demo', 'time' => $this->clock->now()->modify('-2 hours')->format(DATE_ATOM)],
        ];
    }

    /** @return list<array<string, float|string>> */
    private function localEconomics(): array
    {
        return [
            ['event' => 'Synthetic CPI YoY', 'country' => 'US', 'prior' => 2.8, 'consensus' => 2.7, 'actual' => 2.6, 'unit' => '%'],
            ['event' => 'Synthetic Policy Rate', 'country' => 'EU', 'prior' => 3.5, 'consensus' => 3.25, 'actual' => 3.25, 'unit' => '%'],
            ['event' => 'Synthetic Employment Change', 'country' => 'US', 'prior' => 181.0, 'consensus' => 175.0, 'actual' => 188.0, 'unit' => 'K'],
        ];
    }
}
