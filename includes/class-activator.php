<?php
/**
 * Plugin Activator
 *
 * @package QueryMind
 */

namespace QueryMind;

/**
 * Fired during plugin activation.
 */
class Activator {

    /**
     * Activate the plugin.
     *
     * Creates database tables, sets default options, and schedules events.
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::schedule_events();

        // Store activation time for reference
        update_option( 'querymind_activated', time() );

        // Flush rewrite rules for any custom endpoints
        flush_rewrite_rules();
    }

    /**
     * Create custom database tables.
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Query history table
        $sql_history = "CREATE TABLE {$prefix}querymind_history (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            question TEXT NOT NULL,
            generated_sql TEXT NOT NULL,
            execution_time DECIMAL(10,4) DEFAULT NULL,
            row_count INT(11) DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'success',
            error_message TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX user_id (user_id),
            INDEX created_at (created_at),
            INDEX status (status)
        ) {$charset_collate};";

        dbDelta( $sql_history );

        // Saved queries table
        $sql_saved = "CREATE TABLE {$prefix}querymind_saved (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            question TEXT NOT NULL,
            sql_query TEXT NOT NULL,
            chart_type VARCHAR(50) DEFAULT 'table',
            is_favorite TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX user_id (user_id),
            INDEX is_favorite (is_favorite)
        ) {$charset_collate};";

        dbDelta( $sql_saved );

        // Scheduled reports table
        $sql_scheduled = "CREATE TABLE {$prefix}querymind_scheduled (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            saved_query_id BIGINT(20) UNSIGNED NOT NULL,
            frequency VARCHAR(20) NOT NULL,
            recipients TEXT NOT NULL,
            next_run DATETIME NOT NULL,
            last_run DATETIME DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX user_id (user_id),
            INDEX next_run (next_run),
            INDEX status (status)
        ) {$charset_collate};";

        dbDelta( $sql_scheduled );

        // Store database version for future migrations
        update_option( 'querymind_db_version', QUERYMIND_VERSION );
    }

    /**
     * Set default plugin options.
     */
    private static function set_default_options() {
        $defaults = [
            'querymind_ai_provider'    => 'openai',
            'querymind_ai_model'       => 'gpt-4o-mini',
            'querymind_max_rows'       => 1000,
            'querymind_query_timeout'  => 30,
            'querymind_daily_limit'    => 20,
            'querymind_enable_cache'   => true,
            'querymind_cache_duration' => 3600,
            'querymind_allowed_roles'  => [ 'administrator' ],
        ];

        foreach ( $defaults as $option => $value ) {
            if ( get_option( $option ) === false ) {
                update_option( $option, $value );
            }
        }
    }

    /**
     * Schedule cron events.
     */
    private static function schedule_events() {
        if ( ! wp_next_scheduled( 'querymind_cleanup_history' ) ) {
            wp_schedule_event( time(), 'daily', 'querymind_cleanup_history' );
        }

        if ( ! wp_next_scheduled( 'querymind_process_scheduled_reports' ) ) {
            wp_schedule_event( time(), 'hourly', 'querymind_process_scheduled_reports' );
        }
    }
}
