<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\Analytics;

final readonly class OptionMetrics
{
    public function __construct(
        public float $price,
        public float $delta,
        public float $gamma,
        public float $thetaPerYear,
        public float $vegaPerVolPoint,
        public float $rhoPerRatePoint,
        public float $intrinsicValue,
        public float $timeValue,
    ) {
    }
}
