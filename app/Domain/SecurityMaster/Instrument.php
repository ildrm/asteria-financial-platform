<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\SecurityMaster;

use Asteria\FinancialPlatform\Domain\Exception\DomainException;

final readonly class Instrument
{
    /** @param array<string, string> $identifiers */
    public function __construct(
        public InstrumentId $id,
        public string $name,
        public string $symbol,
        public InstrumentType $type,
        public string $currency,
        public ?string $mic,
        public array $identifiers = [],
    ) {
        if ($name === '' || preg_match('/^[A-Z0-9._\/-]{1,32}$/', $symbol) !== 1) {
            throw new DomainException('Instrument name and canonical symbol are required.');
        }
        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new DomainException('Instrument currency must be an ISO 4217 alpha-3 code.');
        }
    }
}
