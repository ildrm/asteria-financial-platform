<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Security;

final class Capabilities
{
    public const ACCESS_PLATFORM = 'asteria_access_platform';
    public const VIEW_MARKET_DATA = 'asteria_view_market_data';
    public const MANAGE_PROVIDERS = 'asteria_manage_providers';
    public const VIEW_AUDIT = 'asteria_view_audit';
    public const MANAGE_PLATFORM = 'asteria_manage_platform';
    public const MANAGE_PORTFOLIOS = 'asteria_manage_portfolios';
    public const PAPER_TRADE = 'asteria_paper_trade';

    /** @return list<string> */
    public static function administratorDefaults(): array
    {
        return [self::ACCESS_PLATFORM, self::VIEW_MARKET_DATA, self::MANAGE_PROVIDERS, self::VIEW_AUDIT, self::MANAGE_PLATFORM, self::MANAGE_PORTFOLIOS, self::PAPER_TRADE];
    }
}
