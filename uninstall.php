<?php

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$administrator = get_role('administrator');
if ($administrator !== null) {
    foreach ([
        'asteria_access_platform',
        'asteria_view_market_data',
        'asteria_manage_providers',
        'asteria_view_audit',
        'asteria_manage_platform',
        'asteria_manage_portfolios',
        'asteria_paper_trade',
    ] as $capability) {
        $administrator->remove_cap($capability);
    }
}

// Financial and audit records are retained by default. An administrator must
// explicitly opt in before uninstalling persistent Asteria data.
if ((bool) get_option('asteria_delete_data_on_uninstall', false) !== true) {
    return;
}

global $wpdb;

$tables = [
    $wpdb->prefix . 'asteria_watchlist_items',
    $wpdb->prefix . 'asteria_orders',
    $wpdb->prefix . 'asteria_positions',
    $wpdb->prefix . 'asteria_portfolios',
    $wpdb->prefix . 'asteria_instrument_identifiers',
    $wpdb->prefix . 'asteria_instruments',
    $wpdb->prefix . 'asteria_audit_events',
];

foreach ($tables as $table) {
    $validated = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    if ($validated !== $table || ! str_starts_with($table, $wpdb->prefix . 'asteria_')) {
        continue;
    }
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Identifier is allow-list validated above.
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}

delete_option('asteria_schema_version');
delete_option('asteria_demo_mode');
delete_option('asteria_delete_data_on_uninstall');
