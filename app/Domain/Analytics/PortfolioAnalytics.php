<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\Analytics;

use Asteria\FinancialPlatform\Domain\Exception\DomainException;

final class PortfolioAnalytics
{
    /** @param list<float> $returns Daily decimal returns. */
    public function calculate(array $returns, float $annualRiskFreeRate = 0.0): PortfolioMetrics
    {
        if (count($returns) < 2) {
            throw new DomainException('At least two returns are required.');
        }
        foreach ($returns as $return) {
            if (! is_finite($return) || $return <= -1.0) {
                throw new DomainException('Portfolio returns must be finite and greater than -100%.');
            }
        }

        $count = count($returns);
        $mean = array_sum($returns) / $count;
        $sumSquared = 0.0;
        foreach ($returns as $return) {
            $sumSquared += ($return - $mean) ** 2;
        }
        $dailyVolatility = sqrt($sumSquared / ($count - 1));
        $annualVolatility = $dailyVolatility * sqrt(252.0);
        $dailyRiskFree = $annualRiskFreeRate / 252.0;
        $sharpe = $dailyVolatility === 0.0 ? 0.0 : (($mean - $dailyRiskFree) / $dailyVolatility) * sqrt(252.0);

        $wealth = 1.0;
        $peak = 1.0;
        $maximumDrawdown = 0.0;
        foreach ($returns as $return) {
            $wealth *= 1.0 + $return;
            $peak = max($peak, $wealth);
            $maximumDrawdown = max($maximumDrawdown, 1.0 - ($wealth / $peak));
        }

        $sorted = $returns;
        sort($sorted, SORT_NUMERIC);
        $tailCount = max(1, (int) ceil($count * 0.05));
        $tail = array_slice($sorted, 0, $tailCount);
        $var95 = max(0.0, -$tail[$tailCount - 1]);
        $expectedShortfall = max(0.0, -(array_sum($tail) / $tailCount));

        return new PortfolioMetrics($wealth - 1.0, $annualVolatility, $sharpe, $maximumDrawdown, $var95, $expectedShortfall);
    }
}
