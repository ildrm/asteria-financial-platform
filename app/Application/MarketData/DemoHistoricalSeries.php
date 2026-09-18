<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Application\MarketData;

use Asteria\FinancialPlatform\Contracts\Provider\MarketDataProviderInterface;
use Asteria\FinancialPlatform\Domain\MarketData\HistoricalBar;
use Asteria\FinancialPlatform\Support\Clock;
use DateTimeImmutable;

final readonly class DemoHistoricalSeries
{
    public function __construct(private MarketDataProviderInterface $provider, private Clock $clock)
    {
    }

    /** @return list<HistoricalBar> */
    public function daily(string $symbol, int $days = 90): array
    {
        $days = max(2, min($days, 730));
        $quote = $this->provider->quote($symbol);
        $seed = (int) sprintf('%u', crc32($quote->symbol));
        $end = new DateTimeImmutable($this->clock->now()->format('Y-m-d') . ' 00:00:00+00:00');
        $bars = [];
        $previous = $quote->last * (0.82 + ($seed % 13) / 100.0);

        for ($offset = $days - 1; $offset >= 0; --$offset) {
            $index = $days - 1 - $offset;
            $date = $end->modify("-{$offset} days");
            $trend = (($quote->last - $previous) / max(1, $days)) * $index;
            $wave = sin(($index + ($seed % 17)) / 5.3) * $quote->last * 0.012
                + cos(($index + ($seed % 11)) / 11.0) * $quote->last * 0.007;
            $close = max(0.0001, $previous + $trend + $wave);
            if ($offset === 0) {
                $close = $quote->last;
            }
            $open = $index === 0 ? $close * 0.997 : $bars[$index - 1]->close;
            $range = max($close * 0.003, abs($close - $open) * 0.55);
            $high = max($open, $close) + $range;
            $low = max(0.0001, min($open, $close) - $range);
            $volume = max(1, (int) round($quote->volume * (0.72 + 0.22 * (1.0 + sin($index / 4.1)))));
            $bars[] = new HistoricalBar($date, $open, $high, $low, $close, $volume, $quote->currency, 'asteria-local-history-v1', true);
        }
        return $bars;
    }

    /** @return list<float> */
    public function returns(string $symbol, int $days = 252): array
    {
        $bars = $this->daily($symbol, $days + 1);
        $returns = [];
        for ($index = 1, $count = count($bars); $index < $count; ++$index) {
            $returns[] = $bars[$index - 1]->close === 0.0 ? 0.0 : ($bars[$index]->close / $bars[$index - 1]->close) - 1.0;
        }
        return $returns;
    }
}
