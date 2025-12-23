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
$ai_provider    = get_option( 'querymind_ai_provider', 'openai' );
$ai_model       = get_option( 'querymind_ai_model', 'gpt-4o-mini' );
$openai_key     = get_option( 'querymind_openai_key', '' );
$anthropic_key  = get_option( 'querymind_anthropic_key', '' );
$max_rows       = get_option( 'querymind_max_rows', 1000 );
$query_timeout  = get_option( 'querymind_query_timeout', 30 );
$daily_limit    = get_option( 'querymind_daily_limit', 20 );
$enable_cache   = get_option( 'querymind_enable_cache', true );
$cache_duration = get_option( 'querymind_cache_duration', 3600 );
$allowed_roles  = get_option( 'querymind_allowed_roles', [ 'administrator' ] );

// Get all roles
$all_roles = wp_roles()->get_names();

// Handle form submission
$settings_saved = false;
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
    $ai_provider    = get_option( 'querymind_ai_provider', 'openai' );
    $ai_model       = get_option( 'querymind_ai_model', 'gpt-4o-mini' );
    $openai_key     = get_option( 'querymind_openai_key', '' );
    $anthropic_key  = get_option( 'querymind_anthropic_key', '' );
    $max_rows       = get_option( 'querymind_max_rows', 1000 );
    $query_timeout  = get_option( 'querymind_query_timeout', 30 );
    $daily_limit    = get_option( 'querymind_daily_limit', 20 );
    $enable_cache   = get_option( 'querymind_enable_cache', true );
    $cache_duration = get_option( 'querymind_cache_duration', 3600 );
    $allowed_roles  = get_option( 'querymind_allowed_roles', [ 'administrator' ] );

    $settings_saved = true;
}

// Define integrations
$integrations = [
    'woocommerce'  => [
        'name'        => 'WooCommerce',
        'description' => __( 'E-commerce platform', 'querymind' ),
        'active'      => class_exists( 'WooCommerce' ),
        'icon'        => 'dashicons-cart',
    ],
    'learndash'    => [
        'name'        => 'LearnDash',
        'description' => __( 'Learning management', 'querymind' ),
        'active'      => defined( 'LEARNDASH_VERSION' ),
        'icon'        => 'dashicons-welcome-learn-more',
    ],
    'memberpress'  => [
        'name'        => 'MemberPress',
        'description' => __( 'Membership plugin', 'querymind' ),
        'active'      => defined( 'MEPR_VERSION' ),
        'icon'        => 'dashicons-groups',
    ],
    'edd'          => [
        'name'        => 'Easy Digital Downloads',
        'description' => __( 'Digital products', 'querymind' ),
        'active'      => defined( 'EDD_VERSION' ),
        'icon'        => 'dashicons-download',
    ],
    'gravityforms' => [
        'name'        => 'Gravity Forms',
        'description' => __( 'Form builder', 'querymind' ),
        'active'      => class_exists( 'GFCommon' ),
        'icon'        => 'dashicons-feedback',
    ],
];
?>
<div class="wrap querymind-wrap">
    <!-- Page Header -->
    <header class="querymind-page-header">
        <div class="querymind-page-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        </div>
        <div>
            <h1 class="querymind-page-title"><?php esc_html_e( 'Settings', 'querymind' ); ?></h1>
            <p class="querymind-page-subtitle"><?php esc_html_e( 'Configure QueryMind to work with your preferred AI provider', 'querymind' ); ?></p>
        </div>
    </header>

    <?php if ( $settings_saved ) : ?>
        <div class="querymind-notice querymind-notice-success" role="alert" style="margin-bottom: 24px;">
            <span class="querymind-notice-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </span>
            <p><?php esc_html_e( 'Settings saved successfully.', 'querymind' ); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" class="querymind-settings-form">
        <?php wp_nonce_field( 'querymind_settings_nonce' ); ?>

        <!-- AI Provider Settings -->
        <section class="querymind-settings-section" aria-labelledby="ai-settings-heading">
            <h2 id="ai-settings-heading">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--qm-olive-leaf);">
                    <path d="M12 8V4H8"></path>
                    <rect width="16" height="12" x="4" y="8" rx="2"></rect>
                    <path d="M2 14h2"></path>
                    <path d="M20 14h2"></path>
                    <path d="M15 13v2"></path>
                    <path d="M9 13v2"></path>
                </svg>
                <?php esc_html_e( 'AI Provider', 'querymind' ); ?>
            </h2>
            <p class="description"><?php esc_html_e( 'Configure your AI provider API keys. At least one provider is required.', 'querymind' ); ?></p>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="ai_provider"><?php esc_html_e( 'Default Provider', 'querymind' ); ?></label>
                    </th>
                    <td>
                        <select name="ai_provider" id="ai_provider" class="querymind-select">
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
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <input type="password" name="openai_key" id="openai_key" class="regular-text" value="<?php echo esc_attr( $openai_key ); ?>" placeholder="sk-..." autocomplete="off">
                            <?php if ( ! empty( $openai_key ) ) : ?>
                                <span class="querymind-status querymind-status-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    <?php esc_html_e( 'Configured', 'querymind' ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %s: OpenAI API URL */
                                esc_html__( 'Get your API key from %s', 'querymind' ),
                                '<a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">platform.openai.com</a>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
                <tr id="openai-model-row">
                    <th scope="row">
                        <label for="ai_model"><?php esc_html_e( 'OpenAI Model', 'querymind' ); ?></label>
                    </th>
                    <td>
                        <select name="ai_model" id="ai_model" class="querymind-select">
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
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <input type="password" name="anthropic_key" id="anthropic_key" class="regular-text" value="<?php echo esc_attr( $anthropic_key ); ?>" placeholder="sk-ant-..." autocomplete="off">
                            <?php if ( ! empty( $anthropic_key ) ) : ?>
                                <span class="querymind-status querymind-status-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    <?php esc_html_e( 'Configured', 'querymind' ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %s: Anthropic console URL */
                                esc_html__( 'Get your API key from %s', 'querymind' ),
                                '<a href="https://console.anthropic.com/" target="_blank" rel="noopener">console.anthropic.com</a>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </table>
        </section>

        <!-- Query Settings -->
        <section class="querymind-settings-section" aria-labelledby="query-settings-heading">
            <h2 id="query-settings-heading">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--qm-olive-leaf);">
                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" x2="8" y1="13" y2="13"></line>
                    <line x1="16" x2="8" y1="17" y2="17"></line>
                    <line x1="10" x2="8" y1="9" y2="9"></line>
                </svg>
                <?php esc_html_e( 'Query Limits', 'querymind' ); ?>
            </h2>
            <p class="description"><?php esc_html_e( 'Configure query limits and safety settings to protect your database.', 'querymind' ); ?></p>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="max_rows"><?php esc_html_e( 'Maximum Rows', 'querymind' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="max_rows" id="max_rows" class="small-text" value="<?php echo esc_attr( $max_rows ); ?>" min="10" max="10000" step="10">
                        <p class="description"><?php esc_html_e( 'Maximum number of rows to return per query (10-10,000).', 'querymind' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="query_timeout"><?php esc_html_e( 'Query Timeout', 'querymind' ); ?></label>
                    </th>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="number" name="query_timeout" id="query_timeout" class="small-text" value="<?php echo esc_attr( $query_timeout ); ?>" min="5" max="120">
                            <span style="color: var(--qm-text-muted);"><?php esc_html_e( 'seconds', 'querymind' ); ?></span>
                        </div>
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
        </section>

        <!-- Cache Settings -->
        <section class="querymind-settings-section" aria-labelledby="cache-settings-heading">
            <h2 id="cache-settings-heading">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--qm-olive-leaf);">
                    <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>
                    <path d="M19 3v4"></path>
                    <path d="M21 5h-4"></path>
                </svg>
                <?php esc_html_e( 'Cache Settings', 'querymind' ); ?>
            </h2>
            <p class="description"><?php esc_html_e( 'Configure caching to improve performance and reduce API calls.', 'querymind' ); ?></p>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Enable Cache', 'querymind' ); ?></th>
                    <td>
                        <label class="querymind-toggle" for="enable_cache">
                            <input type="checkbox" name="enable_cache" id="enable_cache" value="1" <?php checked( $enable_cache ); ?>>
                            <span class="querymind-toggle-slider"></span>
                        </label>
                        <span style="margin-left: 12px; color: var(--qm-text-secondary);">
                            <?php esc_html_e( 'Cache schema information for better performance', 'querymind' ); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="cache_duration"><?php esc_html_e( 'Cache Duration', 'querymind' ); ?></label>
                    </th>
                    <td>
                        <select name="cache_duration" id="cache_duration" class="querymind-select">
                            <option value="1800" <?php selected( $cache_duration, 1800 ); ?>><?php esc_html_e( '30 minutes', 'querymind' ); ?></option>
                            <option value="3600" <?php selected( $cache_duration, 3600 ); ?>><?php esc_html_e( '1 hour', 'querymind' ); ?></option>
                            <option value="7200" <?php selected( $cache_duration, 7200 ); ?>><?php esc_html_e( '2 hours', 'querymind' ); ?></option>
                            <option value="21600" <?php selected( $cache_duration, 21600 ); ?>><?php esc_html_e( '6 hours', 'querymind' ); ?></option>
                            <option value="86400" <?php selected( $cache_duration, 86400 ); ?>><?php esc_html_e( '24 hours', 'querymind' ); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </section>

        <!-- Access Control -->
        <section class="querymind-settings-section" aria-labelledby="access-settings-heading">
            <h2 id="access-settings-heading">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--qm-olive-leaf);">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <?php esc_html_e( 'Access Control', 'querymind' ); ?>
            </h2>
            <p class="description"><?php esc_html_e( 'Configure which user roles can use QueryMind.', 'querymind' ); ?></p>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Allowed Roles', 'querymind' ); ?></th>
                    <td>
                        <fieldset>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px;">
                                <?php foreach ( $all_roles as $role_key => $role_name ) : ?>
                                    <label class="querymind-checkbox">
                                        <input type="checkbox" name="allowed_roles[]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $allowed_roles, true ) ); ?>>
                                        <span><?php echo esc_html( $role_name ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <p class="description" style="margin-top: 12px;"><?php esc_html_e( 'Users with these roles can use QueryMind. Administrators always have access to settings.', 'querymind' ); ?></p>
                    </td>
                </tr>
            </table>
        </section>

        <!-- Detected Integrations -->
        <section class="querymind-settings-section" aria-labelledby="integrations-heading">
            <h2 id="integrations-heading">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--qm-olive-leaf);">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 1v6"></path>
                    <path d="M12 17v6"></path>
                    <path d="m4.22 4.22 4.24 4.24"></path>
                    <path d="m15.54 15.54 4.24 4.24"></path>
                    <path d="M1 12h6"></path>
                    <path d="M17 12h6"></path>
                    <path d="m4.22 19.78 4.24-4.24"></path>
                    <path d="m15.54 8.46 4.24-4.24"></path>
                </svg>
                <?php esc_html_e( 'Detected Integrations', 'querymind' ); ?>
            </h2>
            <p class="description"><?php esc_html_e( 'QueryMind automatically detects installed plugins and adds relevant query context.', 'querymind' ); ?></p>

            <div class="querymind-integrations-list">
                <?php foreach ( $integrations as $key => $integration ) : ?>
                    <div class="querymind-integration <?php echo $integration['active'] ? 'active' : 'inactive'; ?>">
                        <span class="dashicons <?php echo esc_attr( $integration['icon'] ); ?>"></span>
                        <div style="flex: 1;">
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
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <p class="submit">
            <button type="submit" name="querymind_save_settings" class="button button-primary button-hero">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; vertical-align: -4px;">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <?php esc_html_e( 'Save Settings', 'querymind' ); ?>
            </button>
        </p>
    </form>
</div>
