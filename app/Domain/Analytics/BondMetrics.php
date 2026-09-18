<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\Analytics;

final readonly class BondMetrics
{
    public function __construct(
        public float $dirtyPrice,
        public float $cleanPrice,
        public float $accruedInterest,
        public float $currentYield,
        public float $macaulayDuration,
        public float $modifiedDuration,
        public float $convexity,
        public float $dv01,
    ) {
    }
}
