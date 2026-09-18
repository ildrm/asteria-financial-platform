<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\Exception;

final class InstrumentNotFound extends DomainException
{
    public static function forSymbol(string $symbol): self
    {
        return new self(sprintf('No entitled instrument was found for symbol "%s".', $symbol));
    }
}
