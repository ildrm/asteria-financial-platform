<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Application\MarketData;

use Asteria\FinancialPlatform\Domain\MarketData\Quote;
use Asteria\FinancialPlatform\Infrastructure\Providers\ProviderRegistry;

final readonly class GetQuote
{
    public function __construct(private ProviderRegistry $providers)
    {
    }

    public function handle(string $symbol, string $provider = 'demo'): Quote
    {
        $adapter = $this->providers->marketData($provider);
        if (! $adapter->authenticate() || ! $adapter->isEntitled('quote.snapshot', $symbol)) {
            throw new \Asteria\FinancialPlatform\Domain\Exception\InstrumentNotFound('Provider authentication or entitlement denied.');
        }
        return $adapter->quote($symbol);
    }
}
