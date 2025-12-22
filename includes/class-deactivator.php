<?php
/**
 * Plugin Deactivator
 *
 * @package QueryMind
 */

namespace QueryMind;

/**
 * Fired during plugin deactivation.
 */
class Deactivator {

    /**
     * Deactivate the plugin.
     *
     * Clears scheduled events and temporary data.
     * Note: Database tables and options are preserved for reactivation.
     * Use uninstall.php for complete removal.
     */
    public static function deactivate() {
        self::clear_scheduled_events();
        self::clear_transients();
    }

    /**
     * Clear scheduled cron events.
     */
    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook( 'querymind_cleanup_history' );
        wp_clear_scheduled_hook( 'querymind_process_scheduled_reports' );
    }

    /**
     * Clear plugin transients.
     */
    private static function clear_transients() {
        global $wpdb;

        // Delete all QueryMind transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_querymind_%'
             OR option_name LIKE '_transient_timeout_querymind_%'"
        );
    }
}
