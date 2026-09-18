<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Providers\Demo;

use Asteria\FinancialPlatform\Contracts\Provider\MarketDataProviderInterface;
use Asteria\FinancialPlatform\Contracts\Provider\ProviderHealth;
use Asteria\FinancialPlatform\Contracts\Provider\ProviderException;
use Asteria\FinancialPlatform\Contracts\Provider\ProviderFailureKind;
use Asteria\FinancialPlatform\Contracts\Provider\ProviderMetadata;
use Asteria\FinancialPlatform\Contracts\Provider\ThrottleStatus;
use Asteria\FinancialPlatform\Domain\Exception\InstrumentNotFound;
use Asteria\FinancialPlatform\Domain\MarketData\DataQuality;
use Asteria\FinancialPlatform\Domain\MarketData\Quote;
use Asteria\FinancialPlatform\Domain\SecurityMaster\Instrument;
use Asteria\FinancialPlatform\Domain\SecurityMaster\InstrumentId;
use Asteria\FinancialPlatform\Domain\SecurityMaster\InstrumentType;
use Asteria\FinancialPlatform\Support\Clock;
use Throwable;

final class DemoMarketDataProvider implements MarketDataProviderInterface
{
    /**
     * Values are deterministic, fictional, and deliberately not aligned with
     * current markets. This keeps demos reproducible and licensing-safe.
     *
     * @var array<string, array{name:string,type:InstrumentType,currency:string,mic:?string,id:string,bid:float,ask:float,last:float,previous:float,open:float,high:float,low:float,volume:int}>
     */
    private const INSTRUMENTS = [
        'ASTR' => ['name' => 'Asteria Systems (Synthetic)', 'type' => InstrumentType::CommonEquity, 'currency' => 'USD', 'mic' => 'XNAS', 'id' => '01991a42-54d8-7b4b-8e52-8c5f0c40d001', 'bid' => 124.18, 'ask' => 124.22, 'last' => 124.20, 'previous' => 122.75, 'open' => 123.10, 'high' => 125.05, 'low' => 122.90, 'volume' => 1482500],
        'US10Y' => ['name' => 'Synthetic US 10Y Benchmark', 'type' => InstrumentType::GovernmentBond, 'currency' => 'USD', 'mic' => null, 'id' => '01991a42-54d8-7b4b-8e52-8c5f0c40d002', 'bid' => 99.875, 'ask' => 99.906, 'last' => 99.891, 'previous' => 99.812, 'open' => 99.820, 'high' => 100.031, 'low' => 99.750, 'volume' => 842000],
        'EURUSD' => ['name' => 'Synthetic EUR/USD Spot', 'type' => InstrumentType::FxSpot, 'currency' => 'USD', 'mic' => null, 'id' => '01991a42-54d8-7b4b-8e52-8c5f0c40d003', 'bid' => 1.0872, 'ask' => 1.0874, 'last' => 1.0873, 'previous' => 1.0848, 'open' => 1.0851, 'high' => 1.0892, 'low' => 1.0839, 'volume' => 523000],
        'XAUUSD' => ['name' => 'Synthetic Gold Spot', 'type' => InstrumentType::Commodity, 'currency' => 'USD', 'mic' => null, 'id' => '01991a42-54d8-7b4b-8e52-8c5f0c40d004', 'bid' => 2384.10, 'ask' => 2384.50, 'last' => 2384.30, 'previous' => 2371.80, 'open' => 2374.40, 'high' => 2390.20, 'low' => 2368.60, 'volume' => 318400],
        'BTCUSD' => ['name' => 'Synthetic Bitcoin/USD', 'type' => InstrumentType::CryptoPair, 'currency' => 'USD', 'mic' => null, 'id' => '01991a42-54d8-7b4b-8e52-8c5f0c40d005', 'bid' => 64210.00, 'ask' => 64218.00, 'last' => 64214.00, 'previous' => 63190.00, 'open' => 63320.00, 'high' => 64780.00, 'low' => 62950.00, 'volume' => 28510],
    ];

    public function __construct(private readonly Clock $clock)
    {
    }

    public function metadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            id: 'demo',
            displayName: 'Asteria Deterministic Demo',
            capabilities: ['instrument.search', 'quote.snapshot'],
            provenance: 'Locally bundled deterministic synthetic dataset',
            licensing: 'Synthetic demonstration data; redistribution permitted with Asteria',
            timeoutMilliseconds: 250,
            maxRetries: 0,
            quotaRemaining: PHP_INT_MAX,
        );
    }

    public function authenticate(): bool
    {
        return true;
    }

    public function health(): ProviderHealth
    {
        return new ProviderHealth(true, 0, $this->clock->now(), 'Synthetic provider available');
    }

    public function throttleStatus(): ThrottleStatus
    {
        return new ThrottleStatus(PHP_INT_MAX, PHP_INT_MAX, $this->clock->now()->modify('+100 years'));
    }

    public function normalizeError(Throwable $error): ProviderException
    {
        if ($error instanceof ProviderException) {
            return $error;
        }
        $kind = $error instanceof InstrumentNotFound ? ProviderFailureKind::Entitlement : ProviderFailureKind::Unknown;
        return new ProviderException('demo', $kind, 'The demo provider could not fulfill the request.', false, null, $error);
    }

    public function isEntitled(string $capability, ?string $symbol = null): bool
    {
        return in_array($capability, $this->metadata()->capabilities, true)
            && ($symbol === null || isset(self::INSTRUMENTS[strtoupper($symbol)]));
    }

    public function searchInstruments(string $query, int $limit = 20): array
    {
        $needle = strtoupper(trim($query));
        $matches = [];
        foreach (self::INSTRUMENTS as $symbol => $data) {
            if ($needle !== '' && ! str_contains($symbol, $needle) && ! str_contains(strtoupper($data['name']), $needle)) {
                continue;
            }
            $matches[] = new Instrument(
                new InstrumentId($data['id']),
                $data['name'],
                $symbol,
                $data['type'],
                $data['currency'],
                $data['mic'],
                ['INTERNAL' => $data['id'], 'TICKER' => $symbol],
            );
            if (count($matches) >= max(1, min($limit, 100))) {
                break;
            }
        }
        return $matches;
    }

    public function quote(string $symbol): Quote
    {
        $canonical = strtoupper(trim($symbol));
        $data = self::INSTRUMENTS[$canonical] ?? throw InstrumentNotFound::forSymbol($canonical);
        $now = $this->clock->now();

        return new Quote(
            symbol: $canonical,
            currency: $data['currency'],
            bid: $data['bid'],
            ask: $data['ask'],
            last: $data['last'],
            previousClose: $data['previous'],
            open: $data['open'],
            high: $data['high'],
            low: $data['low'],
            volume: $data['volume'],
            marketStatus: 'SYNTHETIC',
            observedAt: $now,
            ingestedAt: $now,
            provider: 'demo',
            source: 'asteria-demo-v1',
            quality: DataQuality::Indicative,
            synthetic: true,
            licensing: 'Synthetic demonstration data',
        );
    }
}
