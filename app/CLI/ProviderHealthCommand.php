<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\CLI;

use Asteria\FinancialPlatform\Infrastructure\Providers\ProviderRegistry;
use WP_CLI;

final readonly class ProviderHealthCommand
{
    public function __construct(private ProviderRegistry $providers)
    {
    }

    /** Display provider health without exposing credentials. */
    public function __invoke(): void
    {
        $rows = [];
        foreach ($this->providers->allMarketData() as $provider) {
            $metadata = $provider->metadata();
            $health = $provider->health();
            $rows[] = [
                'id' => $metadata->id,
                'name' => $metadata->displayName,
                'healthy' => $health->healthy ? 'yes' : 'no',
                'latency_ms' => $health->latencyMilliseconds,
                'checked_at' => $health->checkedAt->format(DATE_ATOM),
            ];
        }
        \WP_CLI\Utils\format_items('table', $rows, ['id', 'name', 'healthy', 'latency_ms', 'checked_at']);
    }
}
