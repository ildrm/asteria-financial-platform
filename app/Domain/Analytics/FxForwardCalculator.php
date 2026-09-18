<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\Analytics;

use Asteria\FinancialPlatform\Domain\Exception\DomainException;

final class FxForwardCalculator
{
    /** Covered-interest-parity forward, quoted as domestic currency per unit of foreign currency. */
    public function outright(float $spot, float $domesticRate, float $foreignRate, float $timeYears): float
    {
        if ($spot <= 0.0 || $timeYears < 0.0 || (1.0 + $foreignRate * $timeYears) <= 0.0) {
            throw new DomainException('FX forward inputs are outside their financial domains.');
        }
        return $spot * (1.0 + $domesticRate * $timeYears) / (1.0 + $foreignRate * $timeYears);
    }

    public function points(float $spot, float $domesticRate, float $foreignRate, float $timeYears): float
    {
        return $this->outright($spot, $domesticRate, $foreignRate, $timeYears) - $spot;
    }
}
