<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\Analytics;

enum OptionType: string
{
    case Call = 'CALL';
    case Put = 'PUT';
}
