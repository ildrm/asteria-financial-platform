<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\Trading;

enum OrderType: string
{
    case Market = 'MARKET';
    case Limit = 'LIMIT';
}
