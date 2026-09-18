<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Contracts\Provider;

final readonly class ProviderMetadata
{
    /** @param list<string> $capabilities */
    public function __construct(
        public string $id,
        public string $displayName,
        public array $capabilities,
        public string $provenance,
        public string $licensing,
        public int $timeoutMilliseconds,
        public int $maxRetries,
        public int $quotaRemaining,
    ) {
    }
}
