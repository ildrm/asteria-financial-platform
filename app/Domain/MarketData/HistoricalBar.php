<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\MarketData;

use DateTimeImmutable;
use JsonSerializable;

final readonly class HistoricalBar implements JsonSerializable
{
    public function __construct(
        public DateTimeImmutable $time,
        public float $open,
        public float $high,
        public float $low,
        public float $close,
        public int $volume,
        public string $currency,
        public string $source,
        public bool $synthetic,
    ) {
        if ($open < 0.0 || $high < max($open, $close) || $low > min($open, $close) || $low < 0.0 || $volume < 0) {
            throw new \Asteria\FinancialPlatform\Domain\Exception\DomainException('Historical OHLCV bounds are inconsistent.');
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'time' => $this->time->format('Y-m-d'),
            'open' => $this->open,
            'high' => $this->high,
            'low' => $this->low,
            'close' => $this->close,
            'volume' => $this->volume,
            'currency' => $this->currency,
            'source' => $this->source,
            'synthetic' => $this->synthetic,
        ];
    }
}
