<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Http\Rest;

use Asteria\FinancialPlatform\Application\MarketData\DemoHistoricalSeries;
use Asteria\FinancialPlatform\Application\Trading\PaperTradingService;
use Asteria\FinancialPlatform\Application\Workspace\LocalWorkspace;
use Asteria\FinancialPlatform\Contracts\Audit\AuditRepository;
use Asteria\FinancialPlatform\Domain\Exception\DomainException;
use Asteria\FinancialPlatform\Domain\Trading\OrderSide;
use Asteria\FinancialPlatform\Domain\Trading\OrderType;
use Asteria\FinancialPlatform\Security\Capabilities;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final readonly class WorkspaceController
{
    public function __construct(
        private LocalWorkspace $workspace,
        private DemoHistoricalSeries $history,
        private PaperTradingService $trading,
        private AuditRepository $audit,
    ) {
    }

    public function registerRoutes(): void
    {
        register_rest_route('asteria/v1', '/workspace', ['methods' => 'GET', 'callback' => [$this, 'snapshot'], 'permission_callback' => static fn (): bool => current_user_can(Capabilities::ACCESS_PLATFORM)]);
        register_rest_route('asteria/v1', '/market-data/history/(?P<symbol>[A-Z0-9._\/-]{1,32})', [
            'methods' => 'GET', 'callback' => [$this, 'history'], 'permission_callback' => static fn (): bool => current_user_can(Capabilities::VIEW_MARKET_DATA),
            'args' => ['days' => ['default' => 90, 'sanitize_callback' => 'absint', 'validate_callback' => static fn ($value): bool => (int) $value >= 2 && (int) $value <= 730]],
        ]);
        register_rest_route('asteria/v1', '/watchlist/(?P<symbol>[A-Z0-9._\/-]{1,32})', [
            ['methods' => 'POST', 'callback' => [$this, 'addWatchlist'], 'permission_callback' => static fn (): bool => current_user_can(Capabilities::VIEW_MARKET_DATA)],
            ['methods' => 'DELETE', 'callback' => [$this, 'removeWatchlist'], 'permission_callback' => static fn (): bool => current_user_can(Capabilities::VIEW_MARKET_DATA)],
        ]);
        register_rest_route('asteria/v1', '/paper-orders', [
            'methods' => 'POST', 'callback' => [$this, 'placeOrder'], 'permission_callback' => static fn (): bool => current_user_can(Capabilities::PAPER_TRADE),
            'args' => [
                'portfolio_id' => ['required' => true, 'sanitize_callback' => 'absint'],
                'symbol' => ['required' => true, 'sanitize_callback' => static fn ($value): string => strtoupper(sanitize_text_field((string) $value)), 'validate_callback' => static fn ($value): bool => preg_match('/^[A-Z0-9._\/-]{1,32}$/', (string) $value) === 1],
                'side' => ['required' => true, 'sanitize_callback' => static fn ($value): string => strtoupper(sanitize_text_field((string) $value)), 'validate_callback' => static fn ($value): bool => OrderSide::tryFrom((string) $value) !== null],
                'order_type' => ['required' => true, 'sanitize_callback' => static fn ($value): string => strtoupper(sanitize_text_field((string) $value)), 'validate_callback' => static fn ($value): bool => OrderType::tryFrom((string) $value) !== null],
                'quantity' => ['required' => true, 'validate_callback' => static fn ($value): bool => is_numeric($value) && (float) $value > 0.0],
                'limit_price' => ['required' => false, 'validate_callback' => static fn ($value): bool => $value === null || (is_numeric($value) && (float) $value > 0.0)],
            ],
        ]);
        register_rest_route('asteria/v1', '/paper-orders/(?P<id>[0-9a-f-]{36})', [
            'methods' => 'DELETE', 'callback' => [$this, 'cancelOrder'], 'permission_callback' => static fn (): bool => current_user_can(Capabilities::PAPER_TRADE),
        ]);
    }

    public function snapshot(): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response(['data' => $this->workspace->snapshot(get_current_user_id())], 200);
        } catch (Throwable $error) {
            do_action('asteria_log_exception', $error, wp_generate_uuid4());
            return new WP_Error('asteria_workspace_failed', __('The standalone workspace could not be loaded.', 'asteria-financial-platform'), ['status' => 500]);
        }
    }

    public function history(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response(['data' => $this->history->daily((string) $request['symbol'], (int) $request->get_param('days'))], 200);
        } catch (DomainException $error) {
            return new WP_Error('asteria_history_failed', $error->getMessage(), ['status' => 404]);
        } catch (Throwable $error) {
            $requestId = wp_generate_uuid4();
            do_action('asteria_log_exception', $error, $requestId);
            return new WP_Error('asteria_history_unavailable', __('Historical data is temporarily unavailable.', 'asteria-financial-platform'), ['status' => 503, 'request_id' => $requestId]);
        }
    }

    public function addWatchlist(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $symbol = strtoupper((string) $request['symbol']);
            $this->workspace->addWatchlist(get_current_user_id(), $symbol);
            $this->audit->append('watchlist.instrument_added', get_current_user_id(), wp_generate_uuid4(), ['symbol' => $symbol]);
            return new WP_REST_Response(['data' => ['symbol' => $symbol]], 201);
        } catch (DomainException $error) {
            return new WP_Error('asteria_watchlist_failed', $error->getMessage(), ['status' => 400]);
        } catch (Throwable $error) {
            $requestId = wp_generate_uuid4();
            do_action('asteria_log_exception', $error, $requestId);
            return new WP_Error('asteria_watchlist_unavailable', __('The watchlist could not be updated.', 'asteria-financial-platform'), ['status' => 500, 'request_id' => $requestId]);
        }
    }

    public function removeWatchlist(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $symbol = strtoupper((string) $request['symbol']);
            $this->workspace->removeWatchlist(get_current_user_id(), $symbol);
            $this->audit->append('watchlist.instrument_removed', get_current_user_id(), wp_generate_uuid4(), ['symbol' => $symbol]);
            return new WP_REST_Response(null, 204);
        } catch (DomainException $error) {
            return new WP_Error('asteria_watchlist_rejected', $error->getMessage(), ['status' => 409]);
        } catch (Throwable $error) {
            $requestId = wp_generate_uuid4();
            do_action('asteria_log_exception', $error, $requestId);
            return new WP_Error('asteria_watchlist_unavailable', __('The watchlist could not be updated.', 'asteria-financial-platform'), ['status' => 500, 'request_id' => $requestId]);
        }
    }

    public function placeOrder(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $idempotencyKey = (string) $request->get_header('Idempotency-Key');
        try {
            $order = $this->trading->place(
                get_current_user_id(),
                (int) $request->get_param('portfolio_id'),
                (string) $request->get_param('symbol'),
                OrderSide::from((string) $request->get_param('side')),
                OrderType::from((string) $request->get_param('order_type')),
                (float) $request->get_param('quantity'),
                $request->get_param('limit_price') === null ? null : (float) $request->get_param('limit_price'),
                $idempotencyKey,
            );
            $this->audit->append('trade.paper_order', get_current_user_id(), wp_generate_uuid4(), ['order_id' => $order['id'], 'portfolio_id' => $order['portfolio_id'], 'symbol' => $order['symbol'], 'status' => $order['status']]);
            return new WP_REST_Response(['data' => $order], 201);
        } catch (DomainException $error) {
            return new WP_Error('asteria_order_rejected', $error->getMessage(), ['status' => 400]);
        } catch (Throwable $error) {
            do_action('asteria_log_exception', $error, wp_generate_uuid4());
            return new WP_Error('asteria_order_failed', __('The paper order could not be processed.', 'asteria-financial-platform'), ['status' => 500]);
        }
    }

    public function cancelOrder(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $order = $this->trading->cancel(get_current_user_id(), (string) $request['id']);
            $this->audit->append('trade.paper_order_cancelled', get_current_user_id(), wp_generate_uuid4(), ['order_id' => $order['id']]);
            return new WP_REST_Response(['data' => $order], 200);
        } catch (DomainException $error) {
            return new WP_Error('asteria_cancel_rejected', $error->getMessage(), ['status' => 409]);
        } catch (Throwable $error) {
            do_action('asteria_log_exception', $error, wp_generate_uuid4());
            return new WP_Error('asteria_cancel_failed', __('The paper order could not be cancelled.', 'asteria-financial-platform'), ['status' => 500]);
        }
    }
}
