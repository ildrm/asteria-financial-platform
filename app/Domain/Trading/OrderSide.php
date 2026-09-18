<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\Trading;

enum OrderSide: string
{
    case Buy = 'BUY';
    case Sell = 'SELL';
}
