<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform;

use Asteria\FinancialPlatform\Admin\HealthPage;
use Asteria\FinancialPlatform\Admin\WorkspacePage;
use Asteria\FinancialPlatform\Application\MarketData\GetQuote;
use Asteria\FinancialPlatform\Application\MarketData\DemoHistoricalSeries;
use Asteria\FinancialPlatform\Application\Trading\PaperTradingService;
use Asteria\FinancialPlatform\Application\Workspace\LocalWorkspace;
use Asteria\FinancialPlatform\CLI\ProviderHealthCommand;
use Asteria\FinancialPlatform\Http\Rest\MarketDataController;
use Asteria\FinancialPlatform\Http\Rest\WorkspaceController;
use Asteria\FinancialPlatform\Infrastructure\Database\Migrator;
use Asteria\FinancialPlatform\Infrastructure\Persistence\WpdbAuditRepository;
use Asteria\FinancialPlatform\Infrastructure\Providers\ProviderRegistry;
use Asteria\FinancialPlatform\Providers\Demo\DemoMarketDataProvider;
use Asteria\FinancialPlatform\Domain\Analytics\PortfolioAnalytics;
use Asteria\FinancialPlatform\Security\CapabilityRegistrar;
use Asteria\FinancialPlatform\Support\SystemClock;
use Throwable;

final class Plugin
{
    private ?ProviderRegistry $providers = null;

    public function boot(): void
    {
        add_action('plugins_loaded', [$this, 'maybeMigrate']);
        add_action('init', [$this, 'loadTranslations']);
        add_action('rest_api_init', [$this, 'registerRestApi']);
        add_action('admin_menu', [$this, 'registerAdmin']);
        add_action('asteria_log_exception', [$this, 'logException'], 10, 2);

        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('asteria provider-health', new ProviderHealthCommand($this->providers()));
        }
    }

    public function activate(): void
    {
        global $wpdb;
        (new Migrator($wpdb))->migrate();
        (new CapabilityRegistrar())->install();
    }

    public function maybeMigrate(): void
    {
        if (get_option('asteria_schema_version') === Migrator::SCHEMA_VERSION) {
            return;
        }
        global $wpdb;
        (new Migrator($wpdb))->migrate();
        (new CapabilityRegistrar())->install();
    }

    public function deactivate(): void
    {
        // Persistent financial and audit data intentionally remains available
        // across deactivation. Scheduled jobs will be added with their modules.
    }

    public function loadTranslations(): void
    {
        load_plugin_textdomain('asteria-financial-platform', false, dirname(plugin_basename(ASTERIA_FP_FILE)) . '/languages');
    }

    public function registerRestApi(): void
    {
        global $wpdb;
        $clock = new SystemClock();
        $controller = new MarketDataController(
            new GetQuote($this->providers()),
            $this->providers(),
            new WpdbAuditRepository($wpdb, $clock),
        );
        $controller->registerRoutes();
        $history = new DemoHistoricalSeries($this->providers()->marketData('demo'), $clock);
        $workspace = new LocalWorkspace($wpdb, $this->providers()->marketData('demo'), $history, new PortfolioAnalytics(), $clock);
        (new WorkspaceController(
            $workspace,
            $history,
            new PaperTradingService($wpdb, $this->providers()->marketData('demo'), $clock),
            new WpdbAuditRepository($wpdb, $clock),
        ))->registerRoutes();
    }

    public function registerAdmin(): void
    {
        (new WorkspacePage())->register();
        (new HealthPage($this->providers()))->register();
    }

    public function logException(Throwable $exception, string $requestId): void
    {
        $record = wp_json_encode([
            'level' => 'error',
            'component' => 'asteria-financial-platform',
            'request_id' => $requestId,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ], JSON_UNESCAPED_SLASHES);
        if (is_string($record)) {
            error_log($record); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }

    private function providers(): ProviderRegistry
    {
        if ($this->providers === null) {
            $this->providers = new ProviderRegistry();
            $this->providers->registerMarketData(new DemoMarketDataProvider(new SystemClock()));
        }
        return $this->providers;
    }
}
