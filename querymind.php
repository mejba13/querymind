<?php
/**
 * Plugin Name:       QueryMind
 * Plugin URI:        https://querymind.io
 * Description:       Ask questions about your WordPress data in plain English. AI-powered database explorer with visual reports.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Engr Mejba Ahmed
 * Author URI:        https://www.mejba.me/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       querymind
 * Domain Path:       /languages
 *
 * @package QueryMind
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'QUERYMIND_VERSION', '1.0.0' );
define( 'QUERYMIND_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QUERYMIND_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QUERYMIND_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'QUERYMIND_API_URL', 'https://api.querymind.io/v1' );
define( 'QUERYMIND_MIN_PHP', '8.0' );
define( 'QUERYMIND_MIN_WP', '6.0' );

/**
 * PSR-4 style autoloader for QueryMind classes
 */
spl_autoload_register( function ( $class ) {
    $prefix = 'QueryMind\\';
    $base_dir = QUERYMIND_PLUGIN_DIR . 'includes/';

    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $parts = explode( '\\', $relative_class );
    $class_name = array_pop( $parts );

    // Convert CamelCase to kebab-case for file names
    // Uses word boundaries to handle acronyms like AI, API, DB properly
    $file_name = 'class-' . strtolower( preg_replace(
        [ '/([a-z])([A-Z])/', '/([A-Z]+)([A-Z][a-z])/' ],
        [ '$1-$2', '$1-$2' ],
        $class_name
    ) ) . '.php';

    // Build path from namespace parts
    $path = $base_dir;
    if ( ! empty( $parts ) ) {
        $path .= strtolower( implode( '/', $parts ) ) . '/';
    }
    $file = $path . $file_name;

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

/**
 * Check PHP version compatibility
 */
function querymind_check_requirements() {
    $errors = [];

    if ( version_compare( PHP_VERSION, QUERYMIND_MIN_PHP, '<' ) ) {
        $errors[] = sprintf(
            /* translators: 1: Current PHP version, 2: Required PHP version */
            __( 'QueryMind requires PHP %2$s or higher. You are running PHP %1$s.', 'querymind' ),
            PHP_VERSION,
            QUERYMIND_MIN_PHP
        );
    }

    if ( version_compare( get_bloginfo( 'version' ), QUERYMIND_MIN_WP, '<' ) ) {
        $errors[] = sprintf(
            /* translators: 1: Current WordPress version, 2: Required WordPress version */
            __( 'QueryMind requires WordPress %2$s or higher. You are running WordPress %1$s.', 'querymind' ),
            get_bloginfo( 'version' ),
            QUERYMIND_MIN_WP
        );
    }

    return $errors;
}

/**
 * Display admin notice for requirement errors
 */
function querymind_requirements_notice() {
    $errors = querymind_check_requirements();
    if ( ! empty( $errors ) ) {
        foreach ( $errors as $error ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
        }
    }
}

/**
 * Initialize plugin
 */
function querymind_init() {
    // Check requirements
    $errors = querymind_check_requirements();
    if ( ! empty( $errors ) ) {
        add_action( 'admin_notices', 'querymind_requirements_notice' );
        return;
    }

    // Load text domain
    load_plugin_textdomain( 'querymind', false, dirname( QUERYMIND_PLUGIN_BASENAME ) . '/languages' );

    // Initialize main plugin class
    return QueryMind\QueryMind::get_instance();
}
add_action( 'plugins_loaded', 'querymind_init' );

/**
 * Activation hook
 */
register_activation_hook( __FILE__, function() {
    require_once QUERYMIND_PLUGIN_DIR . 'includes/class-activator.php';
    QueryMind\Activator::activate();
} );

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, function() {
    require_once QUERYMIND_PLUGIN_DIR . 'includes/class-deactivator.php';
    QueryMind\Deactivator::deactivate();
} );

/**
 * Plugin action links
 */
add_filter( 'plugin_action_links_' . QUERYMIND_PLUGIN_BASENAME, function( $links ) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url( 'admin.php?page=querymind' ),
        esc_html__( 'Query Data', 'querymind' )
    );
    $settings_page_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url( 'admin.php?page=querymind-settings' ),
        esc_html__( 'Settings', 'querymind' )
    );
    array_unshift( $links, $settings_link, $settings_page_link );
    return $links;
} );
