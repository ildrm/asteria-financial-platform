<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Application\Trading;

use Asteria\FinancialPlatform\Contracts\Provider\MarketDataProviderInterface;
use Asteria\FinancialPlatform\Domain\Exception\DomainException;
use Asteria\FinancialPlatform\Domain\Trading\OrderSide;
use Asteria\FinancialPlatform\Domain\Trading\OrderStatus;
use Asteria\FinancialPlatform\Domain\Trading\OrderType;
use Asteria\FinancialPlatform\Support\Clock;
use RuntimeException;
use Throwable;
use wpdb;

final readonly class PaperTradingService
{
    private const MAX_QUANTITY = 100000.0;
    private const MAX_NOTIONAL = 250000.0;

    public function __construct(
        private wpdb $database,
        private MarketDataProviderInterface $provider,
        private Clock $clock,
    ) {
    }

    /** @return array<string, mixed> */
    public function place(
        int $userId,
        int $portfolioId,
        string $symbol,
        OrderSide $side,
        OrderType $type,
        float $quantity,
        ?float $limitPrice,
        string $idempotencyKey,
    ): array {
        if ($quantity <= 0.0 || $quantity > self::MAX_QUANTITY) {
            throw new DomainException('Order quantity is outside the configured limit.');
        }
        if ($type === OrderType::Limit && ($limitPrice === null || $limitPrice <= 0.0)) {
            throw new DomainException('A positive limit price is required for limit orders.');
        }
        if (preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $idempotencyKey) !== 1) {
            throw new DomainException('A valid idempotency key is required.');
        }

        $ordersTable = $this->database->prefix . 'asteria_orders';
        $existing = $this->database->get_row($this->database->prepare("SELECT * FROM {$ordersTable} WHERE user_id = %d AND idempotency_key = %s", $userId, $idempotencyKey), ARRAY_A);
        if (is_array($existing)) {
            return $existing;
        }

        $canonical = strtoupper($symbol);
        $quote = $this->provider->quote($canonical);
        $marketPrice = $side === OrderSide::Buy ? $quote->ask : $quote->bid;
        $notional = $marketPrice * $quantity;
        if ($notional > self::MAX_NOTIONAL) {
            throw new DomainException('Order notional exceeds the standalone paper-trading limit.');
        }

        $shouldFill = $type === OrderType::Market
            || ($side === OrderSide::Buy && (float) $limitPrice >= $quote->ask)
            || ($side === OrderSide::Sell && (float) $limitPrice <= $quote->bid);
        $fillPrice = $shouldFill ? $marketPrice : null;
        $status = $shouldFill ? OrderStatus::Filled : OrderStatus::Open;
        $rejection = null;
        $orderId = wp_generate_uuid4();
        $now = $this->clock->now()->format('Y-m-d H:i:s.u');
        $portfoliosTable = $this->database->prefix . 'asteria_portfolios';
        $positionsTable = $this->database->prefix . 'asteria_positions';

        $this->database->query('START TRANSACTION');
        try {
            $portfolio = $this->database->get_row($this->database->prepare("SELECT * FROM {$portfoliosTable} WHERE id = %d AND user_id = %d FOR UPDATE", $portfolioId, $userId), ARRAY_A);
            if (! is_array($portfolio)) {
                throw new DomainException('The portfolio does not exist or is not accessible.');
            }
            $position = $this->database->get_row($this->database->prepare("SELECT * FROM {$positionsTable} WHERE portfolio_id = %d AND symbol = %s FOR UPDATE", $portfolioId, $canonical), ARRAY_A);
            $heldQuantity = is_array($position) ? (float) $position['quantity'] : 0.0;
            $averageCost = is_array($position) ? (float) $position['average_cost'] : 0.0;
            $cash = (float) $portfolio['cash'];

            if ($shouldFill && $side === OrderSide::Buy && $notional > $cash) {
                $status = OrderStatus::Rejected;
                $fillPrice = null;
                $rejection = 'Insufficient paper cash.';
            }
            if ($shouldFill && $side === OrderSide::Sell && $quantity > $heldQuantity) {
                $status = OrderStatus::Rejected;
                $fillPrice = null;
                $rejection = 'Insufficient paper position; short selling is disabled.';
            }

            if ($status === OrderStatus::Filled && $fillPrice !== null) {
                if ($side === OrderSide::Buy) {
                    $newQuantity = $heldQuantity + $quantity;
                    $newAverageCost = (($heldQuantity * $averageCost) + ($quantity * $fillPrice)) / $newQuantity;
                    $newCash = $cash - ($quantity * $fillPrice);
                } else {
                    $newQuantity = $heldQuantity - $quantity;
                    $newAverageCost = $newQuantity === 0.0 ? 0.0 : $averageCost;
                    $newCash = $cash + ($quantity * $fillPrice);
                }
                if ($newQuantity === 0.0) {
                    $this->database->delete($positionsTable, ['portfolio_id' => $portfolioId, 'symbol' => $canonical], ['%d', '%s']);
                } else {
                    $this->database->query($this->database->prepare(
                        "INSERT INTO {$positionsTable} (portfolio_id, symbol, quantity, average_cost, updated_at) VALUES (%d, %s, %f, %f, %s) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), average_cost = VALUES(average_cost), updated_at = VALUES(updated_at)",
                        $portfolioId,
                        $canonical,
                        $newQuantity,
                        $newAverageCost,
                        $now,
                    ));
                }
                $this->database->update($portfoliosTable, ['cash' => $newCash, 'updated_at' => $now], ['id' => $portfolioId, 'user_id' => $userId], ['%f', '%s'], ['%d', '%d']);
            }

            $inserted = $this->database->insert($ordersTable, [
                'id' => $orderId,
                'user_id' => $userId,
                'portfolio_id' => $portfolioId,
                'symbol' => $canonical,
                'side' => $side->value,
                'order_type' => $type->value,
                'quantity' => $quantity,
                'limit_price' => $limitPrice,
                'fill_price' => $fillPrice,
                'status' => $status->value,
                'rejection_reason' => $rejection,
                'idempotency_key' => $idempotencyKey,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            if ($inserted === false) {
                throw new RuntimeException('The paper order could not be persisted.');
            }
            $this->database->query('COMMIT');
        } catch (Throwable $error) {
            $this->database->query('ROLLBACK');
            throw $error;
        }

        return [
            'id' => $orderId,
            'portfolio_id' => $portfolioId,
            'symbol' => $canonical,
            'side' => $side->value,
            'order_type' => $type->value,
            'quantity' => $quantity,
            'limit_price' => $limitPrice,
            'fill_price' => $fillPrice,
            'status' => $status->value,
            'rejection_reason' => $rejection,
            'created_at' => $now,
        ];
    }

    /** @return array{id:string,status:string} */
    public function cancel(int $userId, string $orderId): array
    {
        if (preg_match('/^[0-9a-f-]{36}$/i', $orderId) !== 1) {
            throw new DomainException('The order identifier is invalid.');
        }
        $table = $this->database->prefix . 'asteria_orders';
        $order = $this->database->get_row($this->database->prepare("SELECT id, status FROM {$table} WHERE id = %s AND user_id = %d", $orderId, $userId), ARRAY_A);
        if (! is_array($order)) {
            throw new DomainException('The order does not exist or is not accessible.');
        }
        if ($order['status'] !== OrderStatus::Open->value) {
            throw new DomainException('Only open paper orders can be cancelled.');
        }
        $updated = $this->database->update(
            $table,
            ['status' => OrderStatus::Cancelled->value, 'updated_at' => $this->clock->now()->format('Y-m-d H:i:s.u')],
            ['id' => $orderId, 'user_id' => $userId, 'status' => OrderStatus::Open->value],
            ['%s', '%s'],
            ['%s', '%d', '%s'],
        );
        if ($updated !== 1) {
            throw new RuntimeException('The order state changed before cancellation completed.');
        }
        return ['id' => $orderId, 'status' => OrderStatus::Cancelled->value];
    }
}
