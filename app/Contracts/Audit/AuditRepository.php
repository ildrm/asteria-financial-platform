<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Contracts\Audit;

interface AuditRepository
{
    /** @param array<string, mixed> $context */
    public function append(string $eventType, int $actorUserId, string $requestId, array $context): void;
}
