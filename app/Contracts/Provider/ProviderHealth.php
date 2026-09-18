<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Contracts\Provider;

use DateTimeImmutable;

final readonly class ProviderHealth
{
    public function __construct(
        public bool $healthy,
        public int $latencyMilliseconds,
        public DateTimeImmutable $checkedAt,
        public ?string $message = null,
    ) {
    }
}
