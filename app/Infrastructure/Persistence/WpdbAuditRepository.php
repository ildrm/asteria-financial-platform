<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Infrastructure\Persistence;

use Asteria\FinancialPlatform\Contracts\Audit\AuditRepository;
use Asteria\FinancialPlatform\Support\Clock;
use RuntimeException;
use Throwable;
use wpdb;

final readonly class WpdbAuditRepository implements AuditRepository
{
    public function __construct(private wpdb $database, private Clock $clock)
    {
    }

    public function append(string $eventType, int $actorUserId, string $requestId, array $context): void
    {
        $table = $this->database->prefix . 'asteria_audit_events';
        $this->database->query('START TRANSACTION');
        try {
            // The locking read serializes the hash-chain head, including its
            // index gap when the table is empty (InnoDB is required by migration).
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table is derived only from the trusted WP prefix.
            $previousHash = (string) ($this->database->get_var("SELECT event_hash FROM `{$table}` ORDER BY id DESC LIMIT 1 FOR UPDATE") ?? str_repeat('0', 64));
            $occurredAt = $this->clock->now()->format('Y-m-d H:i:s.u');
            $payload = wp_json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (! is_string($payload)) {
                throw new RuntimeException('Audit context could not be encoded.');
            }
            $eventHash = hash('sha256', implode('|', [$previousHash, $eventType, (string) $actorUserId, $requestId, $occurredAt, $payload]));

            $result = $this->database->insert(
                $table,
                [
                    'event_type' => $eventType,
                    'actor_user_id' => $actorUserId,
                    'request_id' => $requestId,
                    'context_json' => $payload,
                    'occurred_at' => $occurredAt,
                    'previous_hash' => $previousHash,
                    'event_hash' => $eventHash,
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s', '%s'],
            );
            if ($result === false) {
                throw new RuntimeException('Audit event could not be persisted.');
            }
            $this->database->query('COMMIT');
        } catch (Throwable $error) {
            $this->database->query('ROLLBACK');
            throw $error;
        }
    }
}
