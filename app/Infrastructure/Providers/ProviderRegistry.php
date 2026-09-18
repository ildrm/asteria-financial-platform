<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Infrastructure\Providers;

use Asteria\FinancialPlatform\Contracts\Provider\MarketDataProviderInterface;
use InvalidArgumentException;

final class ProviderRegistry
{
    /** @var array<string, MarketDataProviderInterface> */
    private array $marketDataProviders = [];

    public function registerMarketData(MarketDataProviderInterface $provider): void
    {
        $id = $provider->metadata()->id;
        if (isset($this->marketDataProviders[$id])) {
            throw new InvalidArgumentException(sprintf('Provider "%s" is already registered.', $id));
        }
        $this->marketDataProviders[$id] = $provider;
    }

    public function marketData(string $id): MarketDataProviderInterface
    {
        return $this->marketDataProviders[$id]
            ?? throw new InvalidArgumentException(sprintf('Unknown market-data provider "%s".', $id));
    }

    /** @return list<MarketDataProviderInterface> */
    public function allMarketData(): array
    {
        return array_values($this->marketDataProviders);
    }
}
