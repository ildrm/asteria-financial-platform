<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Contracts\Provider;

use RuntimeException;

final class ProviderException extends RuntimeException
{
    public function __construct(
        public readonly string $providerId,
        public readonly ProviderFailureKind $kind,
        string $safeMessage,
        public readonly bool $retryable,
        public readonly ?int $retryAfterSeconds = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($safeMessage, 0, $previous);
    }
}
