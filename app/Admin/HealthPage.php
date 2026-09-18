<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Admin;

use Asteria\FinancialPlatform\Infrastructure\Database\Migrator;
use Asteria\FinancialPlatform\Infrastructure\Providers\ProviderRegistry;
use Asteria\FinancialPlatform\Security\Capabilities;

final readonly class HealthPage
{
    public function __construct(private ProviderRegistry $providers)
    {
    }

    public function register(): void
    {
        add_submenu_page(
            'asteria-platform',
            __('Asteria System Health', 'asteria-financial-platform'),
            __('System Health', 'asteria-financial-platform'),
            Capabilities::MANAGE_PLATFORM,
            'asteria-system-health',
            [$this, 'render'],
        );
    }

    public function render(): void
    {
        if (! current_user_can(Capabilities::ACCESS_PLATFORM)) {
            wp_die(esc_html__('You are not allowed to access Asteria.', 'asteria-financial-platform'));
        }

        $provider = $this->providers->marketData('demo');
        $health = $provider->health();
        $metadata = $provider->metadata();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Asteria System Health', 'asteria-financial-platform'); ?></h1>
            <p><?php echo esc_html__('Foundation control-plane status. All displayed demo prices are synthetic.', 'asteria-financial-platform'); ?></p>
            <table class="widefat striped" aria-label="<?php echo esc_attr__('Asteria system health', 'asteria-financial-platform'); ?>">
                <tbody>
                    <tr><th scope="row"><?php echo esc_html__('Plugin version', 'asteria-financial-platform'); ?></th><td><?php echo esc_html(ASTERIA_FP_VERSION); ?></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('Schema version', 'asteria-financial-platform'); ?></th><td><?php echo esc_html((string) get_option('asteria_schema_version', __('Not installed', 'asteria-financial-platform'))); ?></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('Demo mode', 'asteria-financial-platform'); ?></th><td><?php echo (bool) get_option('asteria_demo_mode', true) ? esc_html__('Enabled', 'asteria-financial-platform') : esc_html__('Disabled', 'asteria-financial-platform'); ?></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('Provider', 'asteria-financial-platform'); ?></th><td><?php echo esc_html($metadata->displayName); ?></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('Provider health', 'asteria-financial-platform'); ?></th><td><?php echo $health->healthy ? esc_html__('Healthy', 'asteria-financial-platform') : esc_html__('Degraded', 'asteria-financial-platform'); ?></td></tr>
                    <tr><th scope="row"><?php echo esc_html__('Data provenance', 'asteria-financial-platform'); ?></th><td><?php echo esc_html($metadata->provenance); ?></td></tr>
                </tbody>
            </table>
            <p><strong><?php echo esc_html__('Regulatory notice:', 'asteria-financial-platform'); ?></strong> <?php echo esc_html__('Analytics are informational and are not investment advice. No live trading connection is installed.', 'asteria-financial-platform'); ?></p>
        </div>
        <?php
    }
}
