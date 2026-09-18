<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\MarketData;

use Asteria\FinancialPlatform\Domain\Exception\DomainException;
use DateTimeImmutable;
use JsonSerializable;

final readonly class Quote implements JsonSerializable
{
    public function __construct(
        public string $symbol,
        public string $currency,
        public float $bid,
        public float $ask,
        public float $last,
        public float $previousClose,
        public float $open,
        public float $high,
        public float $low,
        public int $volume,
        public string $marketStatus,
        public DateTimeImmutable $observedAt,
        public DateTimeImmutable $ingestedAt,
        public string $provider,
        public string $source,
        public DataQuality $quality,
        public bool $synthetic,
        public string $licensing,
    ) {
        if ($bid < 0 || $ask < 0 || $last < 0 || $volume < 0) {
            throw new DomainException('Quote prices and volume cannot be negative.');
        }
        if ($ask < $bid) {
            throw new DomainException('Quote ask cannot be below bid.');
        }
        if ($high < $low || $last > $high || $last < $low) {
            throw new DomainException('Quote OHLC bounds are inconsistent.');
        }
        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new DomainException('Quote currency must be an ISO 4217 alpha-3 code.');
        }
    }

    public function midpoint(): float
    {
        return ($this->bid + $this->ask) / 2.0;
    }

    public function change(): float
    {
        return $this->last - $this->previousClose;
    }

    public function changePercent(): float
    {
        return $this->previousClose === 0.0 ? 0.0 : ($this->change() / $this->previousClose) * 100.0;
    }

    /** @return array<string, bool|float|int|string> */
    public function jsonSerialize(): array
    {
        return [
            'symbol' => $this->symbol,
            'currency' => $this->currency,
            'bid' => $this->bid,
            'ask' => $this->ask,
            'midpoint' => $this->midpoint(),
            'last' => $this->last,
            'previous_close' => $this->previousClose,
            'change' => $this->change(),
            'change_percent' => $this->changePercent(),
            'open' => $this->open,
            'high' => $this->high,
            'low' => $this->low,
            'volume' => $this->volume,
            'market_status' => $this->marketStatus,
            'observed_at' => $this->observedAt->format(DATE_ATOM),
            'ingested_at' => $this->ingestedAt->format(DATE_ATOM),
            'provider' => $this->provider,
            'source' => $this->source,
            'quality' => $this->quality->value,
            'synthetic' => $this->synthetic,
            'licensing' => $this->licensing,
        ];
    }
}
