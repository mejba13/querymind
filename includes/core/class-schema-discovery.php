<?php
/**
 * Schema Discovery
 *
 * Discovers and maps the WordPress database schema for AI context.
 *
 * @package QueryMind
 */

namespace QueryMind\Core;

/**
 * Schema Discovery class.
 *
 * Analyzes the database structure and provides schema information
 * to the AI for generating accurate SQL queries.
 */
class SchemaDiscovery {

    /**
     * Cache key for schema.
     */
    private const CACHE_KEY = 'querymind_schema';

    /**
     * Cache duration in seconds (1 hour).
     */
    private const CACHE_DURATION = 3600;

    /**
     * WordPress database object.
     *
     * @var \wpdb
     */
    private \wpdb $wpdb;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get database schema for AI context.
     *
     * @param array $options Optional options for schema discovery.
     * @return array
     */
    public function get_schema( array $options = [] ): array {
        // Check cache first
        $cached = $this->get_cached_schema();
        if ( $cached !== false && empty( $options['force_refresh'] ) ) {
            return $cached;
        }

        $schema = [];

        // Get all WordPress tables
        $tables = $this->get_relevant_tables( $options );

        foreach ( $tables as $table ) {
            $columns = $this->get_table_columns( $table );
            $schema[ $table ] = [
                'columns'     => $columns,
                'row_count'   => $this->get_approximate_row_count( $table ),
                'description' => $this->get_table_description( $table ),
            ];
        }

        // Cache the schema
        $this->cache_schema( $schema );

        return $schema;
    }

    /**
     * Get schema formatted for AI prompt.
     *
     * @return string
     */
    public function get_schema_for_prompt(): string {
        $schema = $this->get_schema();
        $lines = [];

        foreach ( $schema as $table => $info ) {
            $cols = array_map( function ( $col ) {
                $nullable = $col['nullable'] ? 'NULL' : 'NOT NULL';
                $key = ! empty( $col['key'] ) ? " [{$col['key']}]" : '';
                return "    - {$col['name']} ({$col['type']}, {$nullable}){$key}";
            }, $info['columns'] );

            $description = ! empty( $info['description'] ) ? " -- {$info['description']}" : '';
            $row_count = $info['row_count'] > 0 ? " (~{$info['row_count']} rows)" : '';

            $lines[] = "{$table}{$description}{$row_count}";
            $lines[] = implode( "\n", $cols );
            $lines[] = '';
        }

        return implode( "\n", $lines );
    }

    /**
     * Get relevant tables based on installed plugins.
     *
     * @param array $options Optional options.
     * @return array
     */
    public function get_relevant_tables( array $options = [] ): array {
        $tables = [];

        // Core WordPress tables
        $core_tables = [
            $this->wpdb->posts,
            $this->wpdb->postmeta,
            $this->wpdb->users,
            $this->wpdb->usermeta,
            $this->wpdb->comments,
            $this->wpdb->commentmeta,
            $this->wpdb->terms,
            $this->wpdb->term_taxonomy,
            $this->wpdb->term_relationships,
            $this->wpdb->options,
        ];

        $tables = array_merge( $tables, $core_tables );

        // WooCommerce tables
        if ( $this->is_plugin_active( 'woocommerce/woocommerce.php' ) || class_exists( 'WooCommerce' ) ) {
            $woo_tables = $this->get_woocommerce_tables();
            $tables = array_merge( $tables, $woo_tables );
        }

        // LearnDash tables
        if ( $this->is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) || defined( 'LEARNDASH_VERSION' ) ) {
            $ld_tables = $this->get_learndash_tables();
            $tables = array_merge( $tables, $ld_tables );
        }

        // MemberPress tables
        if ( $this->is_plugin_active( 'memberpress/memberpress.php' ) || defined( 'MEPR_VERSION' ) ) {
            $mp_tables = $this->get_memberpress_tables();
            $tables = array_merge( $tables, $mp_tables );
        }

        // Easy Digital Downloads tables
        if ( $this->is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) || defined( 'EDD_VERSION' ) ) {
            $edd_tables = $this->get_edd_tables();
            $tables = array_merge( $tables, $edd_tables );
        }

        // Gravity Forms tables
        if ( $this->is_plugin_active( 'gravityforms/gravityforms.php' ) || class_exists( 'GFCommon' ) ) {
            $gf_tables = $this->get_gravity_forms_tables();
            $tables = array_merge( $tables, $gf_tables );
        }

        // Filter to only existing tables
        $tables = array_filter( $tables, function ( $table ) {
            return $this->table_exists( $table );
        } );

        return array_unique( $tables );
    }

    /**
     * Get WooCommerce tables.
     *
     * @return array
     */
    private function get_woocommerce_tables(): array {
        $prefix = $this->wpdb->prefix;

        $tables = [
            $prefix . 'wc_orders',
            $prefix . 'wc_orders_meta',
            $prefix . 'wc_order_stats',
            $prefix . 'wc_order_product_lookup',
            $prefix . 'wc_order_coupon_lookup',
            $prefix . 'wc_customer_lookup',
            $prefix . 'woocommerce_order_items',
            $prefix . 'woocommerce_order_itemmeta',
            $prefix . 'woocommerce_tax_rates',
            $prefix . 'woocommerce_shipping_zones',
            $prefix . 'wc_product_meta_lookup',
        ];

        // Legacy tables (pre-HPOS) - orders in posts table
        if ( ! $this->is_hpos_enabled() ) {
            // posts and postmeta are already included in core tables
        }

        return $tables;
    }

    /**
     * Get LearnDash tables.
     *
     * @return array
     */
    private function get_learndash_tables(): array {
        $prefix = $this->wpdb->prefix;

        return [
            $prefix . 'learndash_user_activity',
            $prefix . 'learndash_user_activity_meta',
            $prefix . 'learndash_pro_quiz_statistic',
            $prefix . 'learndash_pro_quiz_statistic_ref',
        ];
    }

    /**
     * Get MemberPress tables.
     *
     * @return array
     */
    private function get_memberpress_tables(): array {
        $prefix = $this->wpdb->prefix;

        return [
            $prefix . 'mepr_transactions',
            $prefix . 'mepr_subscriptions',
            $prefix . 'mepr_members',
            $prefix . 'mepr_events',
        ];
    }

    /**
     * Get Easy Digital Downloads tables.
     *
     * @return array
     */
    private function get_edd_tables(): array {
        $prefix = $this->wpdb->prefix;

        return [
            $prefix . 'edd_orders',
            $prefix . 'edd_order_items',
            $prefix . 'edd_order_adjustments',
            $prefix . 'edd_customers',
            $prefix . 'edd_customer_email_addresses',
        ];
    }

    /**
     * Get Gravity Forms tables.
     *
     * @return array
     */
    private function get_gravity_forms_tables(): array {
        $prefix = $this->wpdb->prefix;

        return [
            $prefix . 'gf_form',
            $prefix . 'gf_entry',
            $prefix . 'gf_entry_meta',
        ];
    }

    /**
     * Get columns for a table.
     *
     * @param string $table Table name.
     * @return array
     */
    public function get_table_columns( string $table ): array {
        $cache_key = 'querymind_columns_' . md5( $table );
        $cached = wp_cache_get( $cache_key );

        if ( $cached !== false ) {
            return $cached;
        }

        $columns = $this->wpdb->get_results(
            $this->wpdb->prepare(
                'SHOW FULL COLUMNS FROM `%1s`',
                $table
            ),
            ARRAY_A
        );

        if ( empty( $columns ) ) {
            return [];
        }

        $result = array_map( function ( $col ) {
            return [
                'name'     => $col['Field'],
                'type'     => $col['Type'],
                'nullable' => $col['Null'] === 'YES',
                'key'      => $col['Key'],
                'default'  => $col['Default'],
                'extra'    => $col['Extra'] ?? '',
                'comment'  => $col['Comment'] ?? '',
            ];
        }, $columns );

        wp_cache_set( $cache_key, $result, '', self::CACHE_DURATION );

        return $result;
    }

    /**
     * Get approximate row count for table.
     *
     * @param string $table Table name.
     * @return int
     */
    public function get_approximate_row_count( string $table ): int {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT TABLE_ROWS
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = %s",
                $table
            )
        );

        return (int) ( $result->TABLE_ROWS ?? 0 );
    }

    /**
     * Get human-readable description of table.
     *
     * @param string $table Table name.
     * @return string
     */
    public function get_table_description( string $table ): string {
        $prefix = $this->wpdb->prefix;

        $descriptions = [
            // WordPress core
            $this->wpdb->posts              => 'Posts, pages, and custom post types',
            $this->wpdb->postmeta           => 'Post metadata (custom fields)',
            $this->wpdb->users              => 'User accounts',
            $this->wpdb->usermeta           => 'User metadata (profile fields)',
            $this->wpdb->comments           => 'Post comments',
            $this->wpdb->commentmeta        => 'Comment metadata',
            $this->wpdb->terms              => 'Taxonomy terms (categories, tags)',
            $this->wpdb->term_taxonomy      => 'Term taxonomy relationships',
            $this->wpdb->term_relationships => 'Object to term relationships',
            $this->wpdb->options            => 'Site options and settings',

            // WooCommerce
            $prefix . 'wc_orders'                    => 'WooCommerce orders (HPOS)',
            $prefix . 'wc_orders_meta'               => 'Order metadata',
            $prefix . 'wc_order_stats'               => 'Order statistics summary',
            $prefix . 'wc_order_product_lookup'      => 'Order to product lookup',
            $prefix . 'wc_order_coupon_lookup'       => 'Order to coupon lookup',
            $prefix . 'wc_customer_lookup'           => 'Customer information lookup',
            $prefix . 'woocommerce_order_items'      => 'Order line items',
            $prefix . 'woocommerce_order_itemmeta'   => 'Order item metadata',
            $prefix . 'wc_product_meta_lookup'       => 'Product metadata lookup for queries',

            // LearnDash
            $prefix . 'learndash_user_activity'      => 'LearnDash course progress',
            $prefix . 'learndash_user_activity_meta' => 'Activity metadata',
            $prefix . 'learndash_pro_quiz_statistic' => 'Quiz statistics',

            // MemberPress
            $prefix . 'mepr_transactions'   => 'MemberPress payment transactions',
            $prefix . 'mepr_subscriptions'  => 'Recurring subscriptions',
            $prefix . 'mepr_members'        => 'Member records',
            $prefix . 'mepr_events'         => 'Member events log',

            // EDD
            $prefix . 'edd_orders'       => 'Easy Digital Downloads orders',
            $prefix . 'edd_order_items'  => 'Order items',
            $prefix . 'edd_customers'    => 'Customer records',

            // Gravity Forms
            $prefix . 'gf_form'       => 'Gravity Forms form definitions',
            $prefix . 'gf_entry'      => 'Form submissions',
            $prefix . 'gf_entry_meta' => 'Entry metadata',
        ];

        return $descriptions[ $table ] ?? 'Custom table';
    }

    /**
     * Check if a table exists.
     *
     * @param string $table Table name.
     * @return bool
     */
    public function table_exists( string $table ): bool {
        return $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $table
            )
        ) === $table;
    }

    /**
     * Check if a plugin is active.
     *
     * @param string $plugin Plugin slug.
     * @return bool
     */
    private function is_plugin_active( string $plugin ): bool {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active( $plugin );
    }

    /**
     * Check if WooCommerce HPOS is enabled.
     *
     * @return bool
     */
    private function is_hpos_enabled(): bool {
        return get_option( 'woocommerce_custom_orders_table_enabled' ) === 'yes';
    }

    /**
     * Get list of all allowed tables.
     *
     * @return array
     */
    public function get_allowed_tables(): array {
        $cache_key = 'querymind_allowed_tables';
        $cached = wp_cache_get( $cache_key );

        if ( $cached !== false ) {
            return $cached;
        }

        // Get all tables with WordPress prefix
        $tables = $this->wpdb->get_col( 'SHOW TABLES' );
        $prefix = $this->wpdb->prefix;

        $allowed = array_filter( $tables, function ( $table ) use ( $prefix ) {
            return strpos( $table, $prefix ) === 0;
        } );

        wp_cache_set( $cache_key, $allowed, '', self::CACHE_DURATION );

        return $allowed;
    }

    /**
     * Get detected integrations.
     *
     * @return array
     */
    public function get_detected_integrations(): array {
        $integrations = [ 'wordpress' ];

        if ( class_exists( 'WooCommerce' ) ) {
            $integrations[] = 'woocommerce';
        }

        if ( defined( 'LEARNDASH_VERSION' ) ) {
            $integrations[] = 'learndash';
        }

        if ( defined( 'MEPR_VERSION' ) ) {
            $integrations[] = 'memberpress';
        }

        if ( defined( 'EDD_VERSION' ) ) {
            $integrations[] = 'edd';
        }

        if ( class_exists( 'GFCommon' ) ) {
            $integrations[] = 'gravity-forms';
        }

        return $integrations;
    }

    /**
     * Get cached schema.
     *
     * @return array|false
     */
    private function get_cached_schema() {
        return get_transient( self::CACHE_KEY );
    }

    /**
     * Cache the schema.
     *
     * @param array $schema Schema to cache.
     */
    private function cache_schema( array $schema ) {
        set_transient( self::CACHE_KEY, $schema, self::CACHE_DURATION );
    }

    /**
     * Clear schema cache.
     */
    public function clear_cache() {
        delete_transient( self::CACHE_KEY );
        wp_cache_delete( 'querymind_allowed_tables' );
    }
}
