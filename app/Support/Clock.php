<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Support;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
