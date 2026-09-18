<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Http\Rest;

use Asteria\FinancialPlatform\Application\MarketData\GetQuote;
use Asteria\FinancialPlatform\Contracts\Audit\AuditRepository;
use Asteria\FinancialPlatform\Domain\Exception\InstrumentNotFound;
use Asteria\FinancialPlatform\Infrastructure\Providers\ProviderRegistry;
use Asteria\FinancialPlatform\Security\Capabilities;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final readonly class MarketDataController
{
    public function __construct(
        private GetQuote $getQuote,
        private ProviderRegistry $providers,
        private AuditRepository $audit,
    ) {
    }

    public function registerRoutes(): void
    {
        register_rest_route('asteria/v1', '/market-data/quotes/(?P<symbol>[A-Z0-9._\/-]{1,32})', [
            'methods' => 'GET',
            'callback' => [$this, 'quote'],
            'permission_callback' => static fn (): bool => current_user_can(Capabilities::VIEW_MARKET_DATA),
            'args' => [
                'symbol' => [
                    'required' => true,
                    'sanitize_callback' => static fn ($value): string => strtoupper(sanitize_text_field((string) $value)),
                    'validate_callback' => static fn ($value): bool => preg_match('/^[A-Z0-9._\/-]{1,32}$/', (string) $value) === 1,
                ],
                'provider' => [
                    'default' => 'demo',
                    'sanitize_callback' => static fn ($value): string => sanitize_key((string) $value),
                    'validate_callback' => static fn ($value): bool => preg_match('/^[a-z0-9_-]{1,64}$/', (string) $value) === 1,
                ],
            ],
        ]);

        register_rest_route('asteria/v1', '/instruments', [
            'methods' => 'GET',
            'callback' => [$this, 'instruments'],
            'permission_callback' => static fn (): bool => current_user_can(Capabilities::VIEW_MARKET_DATA),
            'args' => [
                'q' => ['default' => '', 'sanitize_callback' => 'sanitize_text_field'],
                'limit' => ['default' => 20, 'sanitize_callback' => 'absint', 'validate_callback' => static fn ($value): bool => (int) $value >= 1 && (int) $value <= 100],
            ],
        ]);
    }

    public function quote(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $requestId = $this->requestId($request);
        try {
            $quote = $this->getQuote->handle((string) $request['symbol'], (string) $request->get_param('provider'));
            $this->audit->append('market_data.quote_viewed', get_current_user_id(), $requestId, [
                'symbol' => $quote->symbol,
                'provider' => $quote->provider,
            ]);
            $response = new WP_REST_Response(['data' => $quote, 'meta' => ['request_id' => $requestId]], 200);
            $response->header('X-Request-ID', $requestId);
            $response->header('Cache-Control', 'private, max-age=5');
            return $response;
        } catch (InstrumentNotFound $exception) {
            return new WP_Error('asteria_instrument_not_found', $exception->getMessage(), ['status' => 404, 'request_id' => $requestId]);
        } catch (Throwable $exception) {
            do_action('asteria_log_exception', $exception, $requestId);
            return new WP_Error('asteria_provider_unavailable', __('Market data is temporarily unavailable.', 'asteria-financial-platform'), ['status' => 503, 'request_id' => $requestId]);
        }
    }

    public function instruments(WP_REST_Request $request): WP_REST_Response
    {
        $requestId = $this->requestId($request);
        $items = $this->providers->marketData('demo')->searchInstruments((string) $request->get_param('q'), (int) $request->get_param('limit'));
        $data = array_map(static fn ($item): array => [
            'id' => (string) $item->id,
            'name' => $item->name,
            'symbol' => $item->symbol,
            'type' => $item->type->value,
            'currency' => $item->currency,
            'mic' => $item->mic,
            'synthetic' => true,
        ], $items);
        $response = new WP_REST_Response(['data' => $data, 'meta' => ['request_id' => $requestId, 'count' => count($data)]], 200);
        $response->header('X-Request-ID', $requestId);
        return $response;
    }

    private function requestId(WP_REST_Request $request): string
    {
        $supplied = (string) $request->get_header('X-Request-ID');
        return preg_match('/^[0-9a-f-]{36}$/i', $supplied) === 1 ? strtolower($supplied) : wp_generate_uuid4();
    }
}
