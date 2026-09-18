<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Infrastructure\Database;

use wpdb;

final readonly class Migrator
{
    public const SCHEMA_VERSION = '2.0.0';

    public function __construct(private wpdb $database)
    {
    }

    public function migrate(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $collation = $this->database->get_charset_collate();
        $instruments = $this->database->prefix . 'asteria_instruments';
        $identifiers = $this->database->prefix . 'asteria_instrument_identifiers';
        $audit = $this->database->prefix . 'asteria_audit_events';
        $portfolios = $this->database->prefix . 'asteria_portfolios';
        $positions = $this->database->prefix . 'asteria_positions';
        $orders = $this->database->prefix . 'asteria_orders';
        $watchlists = $this->database->prefix . 'asteria_watchlist_items';

        dbDelta("CREATE TABLE {$instruments} (
            id binary(16) NOT NULL,
            canonical_symbol varchar(32) NOT NULL,
            name varchar(255) NOT NULL,
            instrument_type varchar(48) NOT NULL,
            currency char(3) NOT NULL,
            mic char(4) NULL,
            status varchar(24) NOT NULL DEFAULT 'ACTIVE',
            valid_from datetime(6) NOT NULL,
            valid_to datetime(6) NULL,
            created_at datetime(6) NOT NULL,
            updated_at datetime(6) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY canonical_symbol (canonical_symbol),
            KEY type_status (instrument_type, status),
            KEY mic_symbol (mic, canonical_symbol)
        ) ENGINE=InnoDB {$collation};");

        dbDelta("CREATE TABLE {$identifiers} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            instrument_id binary(16) NOT NULL,
            scheme varchar(24) NOT NULL,
            value varchar(128) NOT NULL,
            provider varchar(64) NULL,
            valid_from datetime(6) NOT NULL,
            valid_to datetime(6) NULL,
            created_at datetime(6) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY scheme_value_provider (scheme, value, provider),
            KEY instrument_scheme (instrument_id, scheme)
        ) ENGINE=InnoDB {$collation};");

        dbDelta("CREATE TABLE {$audit} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(96) NOT NULL,
            actor_user_id bigint unsigned NOT NULL DEFAULT 0,
            request_id char(36) NOT NULL,
            context_json longtext NOT NULL,
            occurred_at datetime(6) NOT NULL,
            previous_hash char(64) NOT NULL,
            event_hash char(64) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY event_hash (event_hash),
            KEY event_time (event_type, occurred_at),
            KEY actor_time (actor_user_id, occurred_at),
            KEY request_id (request_id)
        ) ENGINE=InnoDB {$collation};");

        dbDelta("CREATE TABLE {$portfolios} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint unsigned NOT NULL,
            name varchar(120) NOT NULL,
            base_currency char(3) NOT NULL DEFAULT 'USD',
            cash decimal(24,8) NOT NULL DEFAULT 100000.00000000,
            created_at datetime(6) NOT NULL,
            updated_at datetime(6) NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) ENGINE=InnoDB {$collation};");

        dbDelta("CREATE TABLE {$positions} (
            portfolio_id bigint unsigned NOT NULL,
            symbol varchar(32) NOT NULL,
            quantity decimal(24,8) NOT NULL,
            average_cost decimal(24,8) NOT NULL,
            updated_at datetime(6) NOT NULL,
            PRIMARY KEY (portfolio_id, symbol),
            KEY symbol (symbol)
        ) ENGINE=InnoDB {$collation};");

        dbDelta("CREATE TABLE {$orders} (
            id char(36) NOT NULL,
            user_id bigint unsigned NOT NULL,
            portfolio_id bigint unsigned NOT NULL,
            symbol varchar(32) NOT NULL,
            side varchar(4) NOT NULL,
            order_type varchar(16) NOT NULL,
            quantity decimal(24,8) NOT NULL,
            limit_price decimal(24,8) NULL,
            fill_price decimal(24,8) NULL,
            status varchar(24) NOT NULL,
            rejection_reason varchar(255) NULL,
            idempotency_key varchar(128) NOT NULL,
            created_at datetime(6) NOT NULL,
            updated_at datetime(6) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_idempotency (user_id, idempotency_key),
            KEY portfolio_time (portfolio_id, created_at),
            KEY user_time (user_id, created_at)
        ) ENGINE=InnoDB {$collation};");

        dbDelta("CREATE TABLE {$watchlists} (
            user_id bigint unsigned NOT NULL,
            symbol varchar(32) NOT NULL,
            created_at datetime(6) NOT NULL,
            PRIMARY KEY (user_id, symbol),
            KEY symbol (symbol)
        ) ENGINE=InnoDB {$collation};");

        update_option('asteria_schema_version', self::SCHEMA_VERSION, false);
        add_option('asteria_demo_mode', true, '', false);
        add_option('asteria_delete_data_on_uninstall', false, '', false);
    }
}
