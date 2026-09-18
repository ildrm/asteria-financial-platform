<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Contracts\Provider;

use Asteria\FinancialPlatform\Domain\MarketData\Quote;
use Asteria\FinancialPlatform\Domain\SecurityMaster\Instrument;
use Throwable;

interface MarketDataProviderInterface
{
    public function metadata(): ProviderMetadata;

    public function authenticate(): bool;

    public function health(): ProviderHealth;

    public function throttleStatus(): ThrottleStatus;

    public function normalizeError(Throwable $error): ProviderException;

    public function isEntitled(string $capability, ?string $symbol = null): bool;

    /** @return list<Instrument> */
    public function searchInstruments(string $query, int $limit = 20): array;

    public function quote(string $symbol): Quote;
}
