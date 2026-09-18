<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\Trading;

enum OrderStatus: string
{
    case New = 'NEW';
    case Open = 'OPEN';
    case Filled = 'FILLED';
    case Rejected = 'REJECTED';
    case Cancelled = 'CANCELLED';
}
