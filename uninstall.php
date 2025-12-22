<?php
/**
 * QueryMind Uninstall
 *
 * Fired when the plugin is uninstalled.
 * Removes all plugin data including database tables and options.
 *
 * @package QueryMind
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Delete plugin options
$options_to_delete = [
    'querymind_ai_provider',
    'querymind_ai_model',
    'querymind_openai_key',
    'querymind_anthropic_key',
    'querymind_max_rows',
    'querymind_query_timeout',
    'querymind_daily_limit',
    'querymind_enable_cache',
    'querymind_cache_duration',
    'querymind_allowed_roles',
    'querymind_license_key',
    'querymind_activated',
    'querymind_db_version',
];

foreach ( $options_to_delete as $option ) {
    delete_option( $option );
}

// Delete transients
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_querymind_%'
     OR option_name LIKE '_transient_timeout_querymind_%'"
);

// Drop custom tables
$tables_to_drop = [
    $wpdb->prefix . 'querymind_history',
    $wpdb->prefix . 'querymind_saved',
    $wpdb->prefix . 'querymind_scheduled',
];

foreach ( $tables_to_drop as $table ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// Clear any scheduled hooks
wp_clear_scheduled_hook( 'querymind_cleanup_history' );
wp_clear_scheduled_hook( 'querymind_process_scheduled_reports' );

// Clear object cache
wp_cache_flush();
