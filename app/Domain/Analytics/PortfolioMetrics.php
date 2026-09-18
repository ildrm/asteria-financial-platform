<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\Analytics;

final readonly class PortfolioMetrics
{
    public function __construct(
        public float $totalReturn,
        public float $annualizedVolatility,
        public float $sharpeRatio,
        public float $maxDrawdown,
        public float $historicalVar95,
        public float $expectedShortfall95,
    ) {
    }
}
