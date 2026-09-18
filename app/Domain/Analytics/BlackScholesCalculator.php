<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\Analytics;

use Asteria\FinancialPlatform\Domain\Exception\DomainException;

/** European Black-Scholes-Merton model with continuous dividend yield. */
final class BlackScholesCalculator
{
    public function calculate(
        OptionType $type,
        float $spot,
        float $strike,
        float $timeYears,
        float $riskFreeRate,
        float $volatility,
        float $dividendYield = 0.0,
    ): OptionMetrics {
        if ($spot <= 0.0 || $strike <= 0.0 || $timeYears <= 0.0 || $volatility <= 0.0) {
            throw new DomainException('Spot, strike, time, and volatility must be positive.');
        }

        $sqrtTime = sqrt($timeYears);
        $d1 = (log($spot / $strike) + ($riskFreeRate - $dividendYield + 0.5 * $volatility ** 2) * $timeYears)
            / ($volatility * $sqrtTime);
        $d2 = $d1 - $volatility * $sqrtTime;
        $discountRate = exp(-$riskFreeRate * $timeYears);
        $discountDividend = exp(-$dividendYield * $timeYears);
        $pdfD1 = exp(-0.5 * $d1 ** 2) / sqrt(2.0 * M_PI);

        if ($type === OptionType::Call) {
            $price = $spot * $discountDividend * $this->cdf($d1) - $strike * $discountRate * $this->cdf($d2);
            $delta = $discountDividend * $this->cdf($d1);
            $theta = -($spot * $discountDividend * $pdfD1 * $volatility) / (2.0 * $sqrtTime)
                - $riskFreeRate * $strike * $discountRate * $this->cdf($d2)
                + $dividendYield * $spot * $discountDividend * $this->cdf($d1);
            $rho = $strike * $timeYears * $discountRate * $this->cdf($d2) / 100.0;
            $intrinsic = max(0.0, $spot - $strike);
        } else {
            $price = $strike * $discountRate * $this->cdf(-$d2) - $spot * $discountDividend * $this->cdf(-$d1);
            $delta = $discountDividend * ($this->cdf($d1) - 1.0);
            $theta = -($spot * $discountDividend * $pdfD1 * $volatility) / (2.0 * $sqrtTime)
                + $riskFreeRate * $strike * $discountRate * $this->cdf(-$d2)
                - $dividendYield * $spot * $discountDividend * $this->cdf(-$d1);
            $rho = -$strike * $timeYears * $discountRate * $this->cdf(-$d2) / 100.0;
            $intrinsic = max(0.0, $strike - $spot);
        }

        $gamma = $discountDividend * $pdfD1 / ($spot * $volatility * $sqrtTime);
        $vega = $spot * $discountDividend * $pdfD1 * $sqrtTime / 100.0;

        return new OptionMetrics($price, $delta, $gamma, $theta, $vega, $rho, $intrinsic, max(0.0, $price - $intrinsic));
    }

    private function cdf(float $x): float
    {
        // Abramowitz-Stegun 7.1.26; maximum absolute error is about 7.5e-8.
        $sign = $x < 0.0 ? -1.0 : 1.0;
        $absolute = abs($x) / sqrt(2.0);
        $t = 1.0 / (1.0 + 0.3275911 * $absolute);
        $erf = 1.0 - (((((1.061405429 * $t - 1.453152027) * $t) + 1.421413741) * $t - 0.284496736) * $t + 0.254829592) * $t * exp(-$absolute ** 2);
        return 0.5 * (1.0 + $sign * $erf);
    }
}
