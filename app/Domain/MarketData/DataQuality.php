<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\MarketData;

enum DataQuality: string
{
    case Good = 'GOOD';
    case Stale = 'STALE';
    case Indicative = 'INDICATIVE';
    case Conflicted = 'CONFLICTED';
}
