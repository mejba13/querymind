<?php
/**
 * Main Plugin Class
 *
 * @package QueryMind
 */

namespace QueryMind;

use QueryMind\Admin\Admin;
use QueryMind\Api\RestController;
use QueryMind\Core\SchemaDiscovery;

/**
 * Main QueryMind plugin class.
 *
 * Singleton pattern ensures only one instance is loaded.
 */
class QueryMind {

    /**
     * Plugin instance.
     *
     * @var QueryMind|null
     */
    private static ?QueryMind $instance = null;

    /**
     * Admin instance.
     *
     * @var Admin|null
     */
    private ?Admin $admin = null;

    /**
     * REST Controller instance.
     *
     * @var RestController|null
     */
    private ?RestController $rest_controller = null;

    /**
     * Schema Discovery instance.
     *
     * @var SchemaDiscovery|null
     */
    private ?SchemaDiscovery $schema_discovery = null;

    /**
     * Get plugin instance.
     *
     * @return QueryMind
     */
    public static function get_instance(): QueryMind {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Initialize components
        add_action( 'init', [ $this, 'init' ] );

        // Admin hooks
        if ( is_admin() ) {
            add_action( 'admin_init', [ $this, 'admin_init' ] );
        }

        // REST API
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

        // Cron handlers
        add_action( 'querymind_cleanup_history', [ $this, 'cleanup_history' ] );
        add_action( 'querymind_process_scheduled_reports', [ $this, 'process_scheduled_reports' ] );

        // AJAX handlers for legacy support
        add_action( 'wp_ajax_querymind_query', [ $this, 'ajax_handle_query' ] );
        add_action( 'wp_ajax_querymind_get_suggestions', [ $this, 'ajax_get_suggestions' ] );
    }

    /**
     * Initialize plugin.
     */
    public function init() {
        // Initialize schema discovery
        $this->schema_discovery = new SchemaDiscovery();

        // Load admin if in admin context
        if ( is_admin() ) {
            $this->admin = new Admin();
        }
    }

    /**
     * Admin initialization.
     */
    public function admin_init() {
        // Register settings
        $this->register_settings();

        // Check for updates or migrations
        $this->check_version();
    }

    /**
     * Register REST API routes.
     */
    public function register_rest_routes() {
        $this->rest_controller = new RestController();
        $this->rest_controller->register_routes();
    }

    /**
     * Register plugin settings.
     */
    private function register_settings() {
        // AI Provider settings
        register_setting( 'querymind_settings', 'querymind_ai_provider', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'openai',
        ] );

        register_setting( 'querymind_settings', 'querymind_openai_key', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ] );

        register_setting( 'querymind_settings', 'querymind_anthropic_key', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ] );

        register_setting( 'querymind_settings', 'querymind_ai_model', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'gpt-4o-mini',
        ] );

        // Query settings
        register_setting( 'querymind_settings', 'querymind_max_rows', [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 1000,
        ] );

        register_setting( 'querymind_settings', 'querymind_query_timeout', [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 30,
        ] );

        register_setting( 'querymind_settings', 'querymind_daily_limit', [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 20,
        ] );

        // Cache settings
        register_setting( 'querymind_settings', 'querymind_enable_cache', [
            'type'              => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => true,
        ] );

        register_setting( 'querymind_settings', 'querymind_cache_duration', [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 3600,
        ] );

        // Access control
        register_setting( 'querymind_settings', 'querymind_allowed_roles', [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize_roles' ],
            'default'           => [ 'administrator' ],
        ] );

        // License
        register_setting( 'querymind_settings', 'querymind_license_key', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ] );
    }

    /**
     * Sanitize roles array.
     *
     * @param array $roles Roles to sanitize.
     * @return array
     */
    public function sanitize_roles( $roles ): array {
        if ( ! is_array( $roles ) ) {
            return [ 'administrator' ];
        }
        return array_map( 'sanitize_text_field', $roles );
    }

    /**
     * Check plugin version and run migrations if needed.
     */
    private function check_version() {
        $stored_version = get_option( 'querymind_db_version', '0.0.0' );

        if ( version_compare( $stored_version, QUERYMIND_VERSION, '<' ) ) {
            // Run migrations if needed
            $this->run_migrations( $stored_version );
            update_option( 'querymind_db_version', QUERYMIND_VERSION );
        }
    }

    /**
     * Run database migrations.
     *
     * @param string $from_version Version to migrate from.
     */
    private function run_migrations( string $from_version ) {
        // Future migrations can be added here
        // Example:
        // if ( version_compare( $from_version, '1.1.0', '<' ) ) {
        //     $this->migrate_to_1_1_0();
        // }
    }

    /**
     * AJAX handler for queries (legacy support).
     */
    public function ajax_handle_query() {
        check_ajax_referer( 'querymind_nonce', 'nonce' );

        if ( ! $this->user_can_query() ) {
            wp_send_json_error( [
                'message' => __( 'You do not have permission to perform queries.', 'querymind' ),
            ] );
        }

        $question = isset( $_POST['question'] ) ? sanitize_textarea_field( wp_unslash( $_POST['question'] ) ) : '';

        if ( empty( $question ) ) {
            wp_send_json_error( [
                'message' => __( 'Please enter a question.', 'querymind' ),
            ] );
        }

        // Forward to REST API logic
        $controller = new RestController();
        $result = $controller->process_query( $question );

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * AJAX handler for suggestions.
     */
    public function ajax_get_suggestions() {
        check_ajax_referer( 'querymind_nonce', 'nonce' );

        if ( ! $this->user_can_query() ) {
            wp_send_json_error( [
                'message' => __( 'You do not have permission.', 'querymind' ),
            ] );
        }

        $suggestions = $this->get_suggestions();
        wp_send_json_success( [ 'suggestions' => $suggestions ] );
    }

    /**
     * Get query suggestions based on detected integrations.
     *
     * @return array
     */
    public function get_suggestions(): array {
        $suggestions = [
            __( 'How many posts do we have?', 'querymind' ),
            __( 'Show me the 10 most recent users', 'querymind' ),
            __( 'How many comments were posted this month?', 'querymind' ),
        ];

        // Add WooCommerce suggestions
        if ( class_exists( 'WooCommerce' ) ) {
            $suggestions = array_merge( $suggestions, [
                __( 'What was our total revenue this month?', 'querymind' ),
                __( 'Show me the top 10 customers by total spend', 'querymind' ),
                __( 'How many orders are pending?', 'querymind' ),
                __( 'What is our average order value?', 'querymind' ),
            ] );
        }

        // Add LearnDash suggestions
        if ( defined( 'LEARNDASH_VERSION' ) ) {
            $suggestions = array_merge( $suggestions, [
                __( 'How many students enrolled this month?', 'querymind' ),
                __( 'What is the completion rate for each course?', 'querymind' ),
                __( 'What is the average quiz score?', 'querymind' ),
            ] );
        }

        // Add MemberPress suggestions
        if ( defined( 'MEPR_VERSION' ) ) {
            $suggestions = array_merge( $suggestions, [
                __( 'How many active members do we have?', 'querymind' ),
                __( 'What is our monthly recurring revenue?', 'querymind' ),
                __( 'Show me members who cancelled this month', 'querymind' ),
            ] );
        }

        return $suggestions;
    }

    /**
     * Check if current user can perform queries.
     *
     * @return bool
     */
    public function user_can_query(): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $allowed_roles = get_option( 'querymind_allowed_roles', [ 'administrator' ] );
        $user = wp_get_current_user();

        foreach ( $allowed_roles as $role ) {
            if ( in_array( $role, $user->roles, true ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cleanup old history entries.
     */
    public function cleanup_history() {
        global $wpdb;

        $table = $wpdb->prefix . 'querymind_history';
        $days_to_keep = apply_filters( 'querymind_history_retention_days', 90 );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days_to_keep
            )
        );
    }

    /**
     * Process scheduled reports.
     */
    public function process_scheduled_reports() {
        global $wpdb;

        $table = $wpdb->prefix . 'querymind_scheduled';
        $now = current_time( 'mysql' );

        $reports = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE status = 'active' AND next_run <= %s",
                $now
            )
        );

        foreach ( $reports as $report ) {
            $this->send_scheduled_report( $report );
        }
    }

    /**
     * Send a scheduled report.
     *
     * @param object $report Report object.
     */
    private function send_scheduled_report( object $report ) {
        // Implementation will be added with the full reporting feature
        // For now, just update the next run time
        global $wpdb;

        $next_run = $this->calculate_next_run( $report->frequency );

        $wpdb->update(
            $wpdb->prefix . 'querymind_scheduled',
            [
                'last_run' => current_time( 'mysql' ),
                'next_run' => $next_run,
            ],
            [ 'id' => $report->id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );
    }

    /**
     * Calculate next run time based on frequency.
     *
     * @param string $frequency Frequency (daily, weekly, monthly).
     * @return string
     */
    private function calculate_next_run( string $frequency ): string {
        $now = new \DateTime( 'now', wp_timezone() );

        switch ( $frequency ) {
            case 'daily':
                $now->modify( '+1 day' );
                break;
            case 'weekly':
                $now->modify( '+1 week' );
                break;
            case 'monthly':
                $now->modify( '+1 month' );
                break;
        }

        return $now->format( 'Y-m-d H:i:s' );
    }

    /**
     * Get schema discovery instance.
     *
     * @return SchemaDiscovery|null
     */
    public function get_schema_discovery(): ?SchemaDiscovery {
        return $this->schema_discovery;
    }

    /**
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Prevent unserialization.
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton' );
    }
}
