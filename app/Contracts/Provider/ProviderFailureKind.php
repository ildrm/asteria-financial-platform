<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Contracts\Provider;

enum ProviderFailureKind: string
{
    case Authentication = 'AUTHENTICATION';
    case Entitlement = 'ENTITLEMENT';
    case RateLimit = 'RATE_LIMIT';
    case Timeout = 'TIMEOUT';
    case MalformedResponse = 'MALFORMED_RESPONSE';
    case Unavailable = 'UNAVAILABLE';
    case Unknown = 'UNKNOWN';
}
