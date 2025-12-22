<?php
/**
 * Settings View
 *
 * @package QueryMind
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get current settings
$ai_provider = get_option( 'querymind_ai_provider', 'openai' );
$ai_model = get_option( 'querymind_ai_model', 'gpt-4o-mini' );
$openai_key = get_option( 'querymind_openai_key', '' );
$anthropic_key = get_option( 'querymind_anthropic_key', '' );
$max_rows = get_option( 'querymind_max_rows', 1000 );
$query_timeout = get_option( 'querymind_query_timeout', 30 );
$daily_limit = get_option( 'querymind_daily_limit', 20 );
$enable_cache = get_option( 'querymind_enable_cache', true );
$cache_duration = get_option( 'querymind_cache_duration', 3600 );
$allowed_roles = get_option( 'querymind_allowed_roles', [ 'administrator' ] );

// Get all roles
$all_roles = wp_roles()->get_names();

// Handle form submission
if ( isset( $_POST['querymind_save_settings'] ) && check_admin_referer( 'querymind_settings_nonce' ) ) {
    // Save settings
    update_option( 'querymind_ai_provider', sanitize_text_field( $_POST['ai_provider'] ?? 'openai' ) );
    update_option( 'querymind_ai_model', sanitize_text_field( $_POST['ai_model'] ?? 'gpt-4o-mini' ) );

    if ( ! empty( $_POST['openai_key'] ) ) {
        update_option( 'querymind_openai_key', sanitize_text_field( $_POST['openai_key'] ) );
    }

    if ( ! empty( $_POST['anthropic_key'] ) ) {
        update_option( 'querymind_anthropic_key', sanitize_text_field( $_POST['anthropic_key'] ) );
    }

    update_option( 'querymind_max_rows', absint( $_POST['max_rows'] ?? 1000 ) );
    update_option( 'querymind_query_timeout', absint( $_POST['query_timeout'] ?? 30 ) );
    update_option( 'querymind_daily_limit', absint( $_POST['daily_limit'] ?? 20 ) );
    update_option( 'querymind_enable_cache', isset( $_POST['enable_cache'] ) );
    update_option( 'querymind_cache_duration', absint( $_POST['cache_duration'] ?? 3600 ) );

    $roles = isset( $_POST['allowed_roles'] ) ? array_map( 'sanitize_text_field', $_POST['allowed_roles'] ) : [ 'administrator' ];
    update_option( 'querymind_allowed_roles', $roles );

    // Refresh values
    $ai_provider = get_option( 'querymind_ai_provider', 'openai' );
    $ai_model = get_option( 'querymind_ai_model', 'gpt-4o-mini' );
    $openai_key = get_option( 'querymind_openai_key', '' );
    $anthropic_key = get_option( 'querymind_anthropic_key', '' );
    $max_rows = get_option( 'querymind_max_rows', 1000 );
    $query_timeout = get_option( 'querymind_query_timeout', 30 );
    $daily_limit = get_option( 'querymind_daily_limit', 20 );
    $enable_cache = get_option( 'querymind_enable_cache', true );
    $cache_duration = get_option( 'querymind_cache_duration', 3600 );
    $allowed_roles = get_option( 'querymind_allowed_roles', [ 'administrator' ] );

    echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully.', 'querymind' ) . '</p></div>';
}
?>
<div class="wrap querymind-wrap">
    <h1>
        <span class="dashicons dashicons-admin-generic"></span>
        <?php esc_html_e( 'QueryMind Settings', 'querymind' ); ?>
    </h1>

    <form method="post" class="querymind-settings-form">
        <?php wp_nonce_field( 'querymind_settings_nonce' ); ?>

        <!-- AI Provider Settings -->
        <div class="querymind-settings-section">
            <h2><?php esc_html_e( 'AI Provider Settings', 'querymind' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Configure your AI provider API keys. At least one provider is required.', 'querymind' ); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="ai_provider"><?php esc_html_e( 'Default Provider', 'querymind' ); ?></label>
                    </th>
                    <td>
                        <select name="ai_provider" id="ai_provider">
                            <option value="openai" <?php selected( $ai_provider, 'openai' ); ?>>OpenAI</option>
                            <option value="anthropic" <?php selected( $ai_provider, 'anthropic' ); ?>>Anthropic (Claude)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="openai_key"><?php esc_html_e( 'OpenAI API Key', 'querymind' ); ?></label>
                    </th>
                    <td>
                        <input type="password" name="openai_key" id="openai_key" class="regular-text" value="<?php echo esc_attr( $openai_key ); ?>" placeholder="sk-...">
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %s: OpenAI API URL */
                                esc_html__( 'Get your API key from %s', 'querymind' ),
                                '<a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>'
                            );
                            ?>
                        </p>
                        <?php if ( ! empty( $openai_key ) ) : ?>
                            <span class="querymind-status querymind-status-success">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e( 'Configured', 'querymind' ); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr id="openai-model-row">
                    <th scope="row">
                        <label for="ai_model"><?php esc_html_e( 'OpenAI Model', 'querymind' ); ?></label>
                    </th>
                    <td>
                        <select name="ai_model" id="ai_model">
                            <option value="gpt-4o" <?php selected( $ai_model, 'gpt-4o' ); ?>>GPT-4o (Most capable)</option>
                            <option value="gpt-4o-mini" <?php selected( $ai_model, 'gpt-4o-mini' ); ?>>GPT-4o Mini (Recommended)</option>
                            <option value="gpt-4-turbo" <?php selected( $ai_model, 'gpt-4-turbo' ); ?>>GPT-4 Turbo</option>
                            <option value="gpt-3.5-turbo" <?php selected( $ai_model, 'gpt-3.5-turbo' ); ?>>GPT-3.5 Turbo (Legacy)</option>
                        </select>
                        <p class="description"><?php esc_html_e( 'GPT-4o Mini offers the best balance of cost and capability.', 'querymind' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="anthropic_key"><?php esc_html_e( 'Anthropic API Key', 'querymind' ); ?></label>
                    </th>
                    <td>
                        <input type="password" name="anthropic_key" id="anthropic_key" class="regular-text" value="<?php echo esc_attr( $anthropic_key ); ?>" placeholder="sk-ant-...">
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %s: Anthropic console URL */
                                esc_html__( 'Get your API key from %s', 'querymind' ),
                                '<a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a>'
                            );
                            ?>
                        </p>
                        <?php if ( ! empty( $anthropic_key ) ) : ?>
                            <span class="querymind-status querymind-status-success">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e( 'Configured', 'querymind' ); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Query Settings -->
        <div class="querymind-settings-section">
            <h2><?php esc_html_e( 'Query Settings', 'querymind' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Configure query limits and safety settings.', 'querymind' ); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="max_rows"><?php esc_html_e( 'Maximum Rows', 'querymind' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="max_rows" id="max_rows" class="small-text" value="<?php echo esc_attr( $max_rows ); ?>" min="10" max="10000">
                        <p class="description"><?php esc_html_e( 'Maximum number of rows to return per query (10-10,000).', 'querymind' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="query_timeout"><?php esc_html_e( 'Query Timeout', 'querymind' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="query_timeout" id="query_timeout" class="small-text" value="<?php echo esc_attr( $query_timeout ); ?>" min="5" max="120">
                        <span><?php esc_html_e( 'seconds', 'querymind' ); ?></span>
                        <p class="description"><?php esc_html_e( 'Maximum time allowed for query execution (5-120 seconds).', 'querymind' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="daily_limit"><?php esc_html_e( 'Daily Query Limit', 'querymind' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="daily_limit" id="daily_limit" class="small-text" value="<?php echo esc_attr( $daily_limit ); ?>" min="0">
                        <p class="description"><?php esc_html_e( 'Maximum queries per user per day. Set to 0 for unlimited.', 'querymind' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Cache Settings -->
        <div class="querymind-settings-section">
            <h2><?php esc_html_e( 'Cache Settings', 'querymind' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Configure caching to improve performance.', 'querymind' ); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Enable Cache', 'querymind' ); ?></th>
                    <td>
                        <label for="enable_cache">
                            <input type="checkbox" name="enable_cache" id="enable_cache" value="1" <?php checked( $enable_cache ); ?>>
                            <?php esc_html_e( 'Cache schema information for better performance', 'querymind' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="cache_duration"><?php esc_html_e( 'Cache Duration', 'querymind' ); ?></label>
                    </th>
                    <td>
                        <select name="cache_duration" id="cache_duration">
                            <option value="1800" <?php selected( $cache_duration, 1800 ); ?>><?php esc_html_e( '30 minutes', 'querymind' ); ?></option>
                            <option value="3600" <?php selected( $cache_duration, 3600 ); ?>><?php esc_html_e( '1 hour', 'querymind' ); ?></option>
                            <option value="7200" <?php selected( $cache_duration, 7200 ); ?>><?php esc_html_e( '2 hours', 'querymind' ); ?></option>
                            <option value="21600" <?php selected( $cache_duration, 21600 ); ?>><?php esc_html_e( '6 hours', 'querymind' ); ?></option>
                            <option value="86400" <?php selected( $cache_duration, 86400 ); ?>><?php esc_html_e( '24 hours', 'querymind' ); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Access Control -->
        <div class="querymind-settings-section">
            <h2><?php esc_html_e( 'Access Control', 'querymind' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Configure which user roles can use QueryMind.', 'querymind' ); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Allowed Roles', 'querymind' ); ?></th>
                    <td>
                        <fieldset>
                            <?php foreach ( $all_roles as $role_key => $role_name ) : ?>
                                <label>
                                    <input type="checkbox" name="allowed_roles[]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $allowed_roles, true ) ); ?>>
                                    <?php echo esc_html( $role_name ); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </fieldset>
                        <p class="description"><?php esc_html_e( 'Users with these roles can use QueryMind. Administrators always have access to settings.', 'querymind' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Detected Integrations -->
        <div class="querymind-settings-section">
            <h2><?php esc_html_e( 'Detected Integrations', 'querymind' ); ?></h2>
            <p class="description"><?php esc_html_e( 'QueryMind automatically detects installed plugins and adds relevant query context.', 'querymind' ); ?></p>

            <div class="querymind-integrations-list">
                <?php
                $integrations = [
                    'woocommerce'  => [
                        'name'   => 'WooCommerce',
                        'active' => class_exists( 'WooCommerce' ),
                        'icon'   => 'dashicons-cart',
                    ],
                    'learndash'    => [
                        'name'   => 'LearnDash',
                        'active' => defined( 'LEARNDASH_VERSION' ),
                        'icon'   => 'dashicons-welcome-learn-more',
                    ],
                    'memberpress'  => [
                        'name'   => 'MemberPress',
                        'active' => defined( 'MEPR_VERSION' ),
                        'icon'   => 'dashicons-groups',
                    ],
                    'edd'          => [
                        'name'   => 'Easy Digital Downloads',
                        'active' => defined( 'EDD_VERSION' ),
                        'icon'   => 'dashicons-download',
                    ],
                    'gravityforms' => [
                        'name'   => 'Gravity Forms',
                        'active' => class_exists( 'GFCommon' ),
                        'icon'   => 'dashicons-feedback',
                    ],
                ];

                foreach ( $integrations as $key => $integration ) :
                    ?>
                    <div class="querymind-integration <?php echo $integration['active'] ? 'active' : 'inactive'; ?>">
                        <span class="dashicons <?php echo esc_attr( $integration['icon'] ); ?>"></span>
                        <span class="integration-name"><?php echo esc_html( $integration['name'] ); ?></span>
                        <?php if ( $integration['active'] ) : ?>
                            <span class="integration-status">
                                <span class="dashicons dashicons-yes"></span>
                                <?php esc_html_e( 'Active', 'querymind' ); ?>
                            </span>
                        <?php else : ?>
                            <span class="integration-status">
                                <?php esc_html_e( 'Not installed', 'querymind' ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="querymind_save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'querymind' ); ?>">
        </p>
    </form>
</div>
