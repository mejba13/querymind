<?php
/**
 * Admin Class
 *
 * Handles admin pages, menus, and assets.
 *
 * @package QueryMind
 */

namespace QueryMind\Admin;

/**
 * Admin class.
 *
 * Manages all WordPress admin functionality.
 */
class Admin {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks(): void {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Add admin menu pages.
     */
    public function add_admin_menu(): void {
        // Main menu
        add_menu_page(
            __( 'QueryMind', 'querymind' ),
            __( 'QueryMind', 'querymind' ),
            'read',
            'querymind',
            [ $this, 'render_main_page' ],
            'dashicons-database-view',
            30
        );

        // Query page (same as main)
        add_submenu_page(
            'querymind',
            __( 'Query Data', 'querymind' ),
            __( 'Query Data', 'querymind' ),
            'read',
            'querymind',
            [ $this, 'render_main_page' ]
        );

        // History page
        add_submenu_page(
            'querymind',
            __( 'Query History', 'querymind' ),
            __( 'History', 'querymind' ),
            'read',
            'querymind-history',
            [ $this, 'render_history_page' ]
        );

        // Saved queries page
        add_submenu_page(
            'querymind',
            __( 'Saved Queries', 'querymind' ),
            __( 'Saved Queries', 'querymind' ),
            'read',
            'querymind-saved',
            [ $this, 'render_saved_page' ]
        );

        // Settings page (admin only)
        add_submenu_page(
            'querymind',
            __( 'Settings', 'querymind' ),
            __( 'Settings', 'querymind' ),
            'manage_options',
            'querymind-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_assets( string $hook ): void {
        // Only load on QueryMind pages
        if ( strpos( $hook, 'querymind' ) === false ) {
            return;
        }

        // Check if build files exist
        $build_js = QUERYMIND_PLUGIN_DIR . 'build/index.js';
        $build_css = QUERYMIND_PLUGIN_DIR . 'build/index.css';

        if ( file_exists( $build_js ) ) {
            // Production React build
            wp_enqueue_script(
                'querymind-app',
                QUERYMIND_PLUGIN_URL . 'build/index.js',
                [ 'wp-element', 'wp-components', 'wp-api-fetch' ],
                QUERYMIND_VERSION,
                true
            );
        } else {
            // Development fallback - inline React
            wp_enqueue_script(
                'querymind-admin',
                QUERYMIND_PLUGIN_URL . 'assets/js/admin.js',
                [ 'jquery', 'wp-api-fetch' ],
                QUERYMIND_VERSION,
                true
            );
        }

        if ( file_exists( $build_css ) ) {
            wp_enqueue_style(
                'querymind-app',
                QUERYMIND_PLUGIN_URL . 'build/index.css',
                [ 'wp-components' ],
                QUERYMIND_VERSION
            );
        }

        // Always load admin CSS
        wp_enqueue_style(
            'querymind-admin',
            QUERYMIND_PLUGIN_URL . 'assets/css/admin.css',
            [],
            QUERYMIND_VERSION
        );

        // Localize script
        wp_localize_script(
            file_exists( $build_js ) ? 'querymind-app' : 'querymind-admin',
            'queryMindData',
            [
                'restUrl'      => rest_url( 'querymind/v1/' ),
                'nonce'        => wp_create_nonce( 'wp_rest' ),
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'ajaxNonce'    => wp_create_nonce( 'querymind_nonce' ),
                'isConfigured' => $this->is_ai_configured(),
                'settingsUrl'  => admin_url( 'admin.php?page=querymind-settings' ),
                'strings'      => [
                    'askPlaceholder'   => __( 'Ask a question about your data...', 'querymind' ),
                    'loading'          => __( 'Thinking...', 'querymind' ),
                    'noResults'        => __( 'No results found.', 'querymind' ),
                    'error'            => __( 'An error occurred. Please try again.', 'querymind' ),
                    'exportCsv'        => __( 'Export CSV', 'querymind' ),
                    'exportJson'       => __( 'Export JSON', 'querymind' ),
                    'save'             => __( 'Save Query', 'querymind' ),
                    'configureApi'     => __( 'Please configure your AI API key in settings to start querying.', 'querymind' ),
                    'rows'             => __( 'rows', 'querymind' ),
                    'executionTime'    => __( 'Execution time', 'querymind' ),
                    'suggestions'      => __( 'Try asking:', 'querymind' ),
                ],
            ]
        );
    }

    /**
     * Check if AI is configured.
     *
     * @return bool
     */
    private function is_ai_configured(): bool {
        $openai_key = get_option( 'querymind_openai_key', '' );
        $anthropic_key = get_option( 'querymind_anthropic_key', '' );
        return ! empty( $openai_key ) || ! empty( $anthropic_key );
    }

    /**
     * Register settings.
     */
    public function register_settings(): void {
        // Settings are registered in the main QueryMind class
    }

    /**
     * Render main query page.
     */
    public function render_main_page(): void {
        if ( ! $this->user_can_access() ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'querymind' ) );
        }

        include QUERYMIND_PLUGIN_DIR . 'includes/admin/views/chat-interface.php';
    }

    /**
     * Render history page.
     */
    public function render_history_page(): void {
        if ( ! $this->user_can_access() ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'querymind' ) );
        }

        include QUERYMIND_PLUGIN_DIR . 'includes/admin/views/history.php';
    }

    /**
     * Render saved queries page.
     */
    public function render_saved_page(): void {
        if ( ! $this->user_can_access() ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'querymind' ) );
        }

        include QUERYMIND_PLUGIN_DIR . 'includes/admin/views/saved-queries.php';
    }

    /**
     * Render settings page.
     */
    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'querymind' ) );
        }

        include QUERYMIND_PLUGIN_DIR . 'includes/admin/views/settings.php';
    }

    /**
     * Check if current user can access QueryMind.
     *
     * @return bool
     */
    private function user_can_access(): bool {
        $allowed_roles = get_option( 'querymind_allowed_roles', [ 'administrator' ] );
        $user = wp_get_current_user();

        foreach ( $allowed_roles as $role ) {
            if ( in_array( $role, $user->roles, true ) ) {
                return true;
            }
        }

        return false;
    }
}
