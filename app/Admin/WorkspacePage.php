<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Admin;

use Asteria\FinancialPlatform\Security\Capabilities;

final class WorkspacePage
{
    private string $hook = '';

    public function register(): void
    {
        $this->hook = (string) add_menu_page(
            __('Asteria Workspace', 'asteria-financial-platform'),
            __('Asteria', 'asteria-financial-platform'),
            Capabilities::ACCESS_PLATFORM,
            'asteria-platform',
            [$this, 'render'],
            'dashicons-chart-area',
            3,
        );
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(string $hook): void
    {
        if ($hook !== $this->hook) {
            return;
        }
        wp_enqueue_style('asteria-workspace', plugins_url('resources/css/workspace.css', ASTERIA_FP_FILE), [], ASTERIA_FP_VERSION);
        wp_enqueue_script('asteria-workspace', plugins_url('resources/js/workspace.js', ASTERIA_FP_FILE), [], ASTERIA_FP_VERSION, true);
        wp_localize_script('asteria-workspace', 'asteriaConfig', [
            'restUrl' => esc_url_raw(rest_url('asteria/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'locale' => get_user_locale(),
            'currency' => 'USD',
            'strings' => [
                'loading' => __('Loading standalone workspace…', 'asteria-financial-platform'),
                'error' => __('The local workspace could not be loaded.', 'asteria-financial-platform'),
                'synthetic' => __('Synthetic demo data', 'asteria-financial-platform'),
            ],
        ]);
    }

    public function render(): void
    {
        if (! current_user_can(Capabilities::ACCESS_PLATFORM)) {
            wp_die(esc_html__('You are not allowed to access Asteria.', 'asteria-financial-platform'));
        }
        ?>
        <div class="wrap asteria-wrap">
            <a class="asteria-skip" href="#asteria-main"><?php echo esc_html__('Skip to workspace content', 'asteria-financial-platform'); ?></a>
            <div id="asteria-workspace" aria-live="polite">
                <header class="asteria-loading">
                    <span class="asteria-mark" aria-hidden="true">A</span>
                    <div><h1><?php echo esc_html__('Asteria', 'asteria-financial-platform'); ?></h1><p><?php echo esc_html__('Loading standalone workspace…', 'asteria-financial-platform'); ?></p></div>
                </header>
            </div>
            <noscript><p class="notice notice-error"><?php echo esc_html__('JavaScript is required for the Asteria workspace.', 'asteria-financial-platform'); ?></p></noscript>
        </div>
        <?php
    }
}
