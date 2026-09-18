<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\SecurityMaster;

use Asteria\FinancialPlatform\Domain\Exception\DomainException;

final readonly class InstrumentId
{
    public function __construct(public string $value)
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) !== 1) {
            throw new DomainException('Instrument identifier must be a valid UUID.');
        }
    }

    public function __toString(): string
    {
        return strtolower($this->value);
    }
}
