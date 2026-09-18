<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Contracts\Provider;

use DateTimeImmutable;

final readonly class ThrottleStatus
{
    public function __construct(
        public int $limit,
        public int $remaining,
        public DateTimeImmutable $resetsAt,
    ) {
    }
}
