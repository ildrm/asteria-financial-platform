<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\Analytics;

use Asteria\FinancialPlatform\Domain\Exception\DomainException;

/**
 * Deterministic analytics for a bullet, fixed-rate bond on regular periods.
 * Rates are decimal values (5% = 0.05); prices are per supplied face value.
 */
final class FixedCouponBondCalculator
{
    public function calculate(
        float $face,
        float $annualCouponRate,
        float $annualYield,
        float $yearsToMaturity,
        int $frequency = 2,
        float $fractionOfCouponPeriodAccrued = 0.0,
    ): BondMetrics {
        if ($face <= 0.0 || $annualCouponRate < 0.0 || $annualYield <= -1.0 || $yearsToMaturity <= 0.0) {
            throw new DomainException('Bond inputs are outside their financial domains.');
        }
        if (! in_array($frequency, [1, 2, 4, 12], true)) {
            throw new DomainException('Coupon frequency must be annual, semiannual, quarterly, or monthly.');
        }
        if ($fractionOfCouponPeriodAccrued < 0.0 || $fractionOfCouponPeriodAccrued >= 1.0) {
            throw new DomainException('Accrued coupon fraction must be in [0, 1).');
        }

        $periodsExact = $yearsToMaturity * $frequency;
        $periods = (int) round($periodsExact);
        if (abs($periods - $periodsExact) > 1.0e-9) {
            throw new DomainException('Maturity must resolve to a whole coupon period.');
        }

        $coupon = $face * $annualCouponRate / $frequency;
        $periodicYield = $annualYield / $frequency;
        if ($periodicYield <= -1.0) {
            throw new DomainException('Yield produces an invalid per-period discount factor.');
        }

        $dirtyPrice = 0.0;
        $weightedTime = 0.0;
        $convexityNumerator = 0.0;
        for ($period = 1; $period <= $periods; ++$period) {
            $cashFlow = $coupon + ($period === $periods ? $face : 0.0);
            $presentValue = $cashFlow / ((1.0 + $periodicYield) ** $period);
            $timeYears = $period / $frequency;
            $dirtyPrice += $presentValue;
            $weightedTime += $timeYears * $presentValue;
            $convexityNumerator += $cashFlow * $period * ($period + 1)
                / ((1.0 + $periodicYield) ** ($period + 2));
        }

        $accruedInterest = $coupon * $fractionOfCouponPeriodAccrued;
        $cleanPrice = $dirtyPrice - $accruedInterest;
        $macaulayDuration = $weightedTime / $dirtyPrice;
        $modifiedDuration = $macaulayDuration / (1.0 + $periodicYield);
        $convexity = $convexityNumerator / ($dirtyPrice * ($frequency ** 2));
        $dv01 = $modifiedDuration * $dirtyPrice * 0.0001;

        return new BondMetrics(
            dirtyPrice: $dirtyPrice,
            cleanPrice: $cleanPrice,
            accruedInterest: $accruedInterest,
            currentYield: $cleanPrice === 0.0 ? 0.0 : ($face * $annualCouponRate) / $cleanPrice,
            macaulayDuration: $macaulayDuration,
            modifiedDuration: $modifiedDuration,
            convexity: $convexity,
            dv01: $dv01,
        );
    }
}
