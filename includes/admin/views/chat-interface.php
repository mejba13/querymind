<?php
/**
 * Chat Interface View
 *
 * @package QueryMind
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap querymind-wrap">
    <h1 class="querymind-header">
        <span class="dashicons dashicons-database-view"></span>
        <?php esc_html_e( 'QueryMind', 'querymind' ); ?>
    </h1>

    <div id="querymind-app" class="querymind-container">
        <!-- React app will mount here -->
        <noscript>
            <div class="querymind-noscript">
                <?php esc_html_e( 'JavaScript is required to use QueryMind.', 'querymind' ); ?>
            </div>
        </noscript>

        <!-- Fallback UI when React build is not available -->
        <div class="querymind-fallback" id="querymind-fallback">
            <div class="querymind-chat-container">
                <!-- Header -->
                <div class="querymind-chat-header">
                    <h2><?php esc_html_e( 'Ask Your Data', 'querymind' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e( 'Ask questions about your WordPress data in plain English.', 'querymind' ); ?>
                    </p>
                </div>

                <!-- API Key Notice -->
                <?php if ( empty( get_option( 'querymind_openai_key' ) ) && empty( get_option( 'querymind_anthropic_key' ) ) ) : ?>
                    <div class="querymind-notice querymind-notice-warning">
                        <span class="dashicons dashicons-warning"></span>
                        <p>
                            <?php
                            printf(
                                /* translators: %s: settings page URL */
                                esc_html__( 'No AI provider configured. Please add your API key in %s to start querying.', 'querymind' ),
                                '<a href="' . esc_url( admin_url( 'admin.php?page=querymind-settings' ) ) . '">' . esc_html__( 'Settings', 'querymind' ) . '</a>'
                            );
                            ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Messages Container -->
                <div class="querymind-messages" id="querymind-messages">
                    <div class="querymind-welcome">
                        <div class="querymind-suggestions">
                            <p class="suggestions-label"><?php esc_html_e( 'Try asking:', 'querymind' ); ?></p>
                            <div class="suggestion-chips" id="querymind-suggestions">
                                <!-- Suggestions will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Container -->
                <div class="querymind-input-container">
                    <form id="querymind-form" class="querymind-form">
                        <div class="querymind-input-wrapper">
                            <textarea
                                id="querymind-input"
                                class="querymind-input"
                                placeholder="<?php esc_attr_e( 'Ask a question about your data...', 'querymind' ); ?>"
                                rows="1"
                                <?php echo ( empty( get_option( 'querymind_openai_key' ) ) && empty( get_option( 'querymind_anthropic_key' ) ) ) ? 'disabled' : ''; ?>
                            ></textarea>
                            <button type="submit" class="querymind-submit" id="querymind-submit" disabled>
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </button>
                        </div>
                    </form>
                    <p class="querymind-input-hint">
                        <?php esc_html_e( 'Press Enter to submit, Shift+Enter for new line', 'querymind' ); ?>
                    </p>
                </div>
            </div>

            <!-- Results Panel -->
            <div class="querymind-results-panel" id="querymind-results" style="display: none;">
                <div class="querymind-results-header">
                    <h3 id="querymind-results-title"><?php esc_html_e( 'Results', 'querymind' ); ?></h3>
                    <div class="querymind-results-actions">
                        <button class="button" id="querymind-export-csv">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e( 'Export CSV', 'querymind' ); ?>
                        </button>
                        <button class="button" id="querymind-save-query">
                            <span class="dashicons dashicons-star-empty"></span>
                            <?php esc_html_e( 'Save Query', 'querymind' ); ?>
                        </button>
                    </div>
                </div>

                <div class="querymind-results-meta" id="querymind-results-meta">
                    <!-- Meta info will be shown here -->
                </div>

                <div class="querymind-results-content" id="querymind-results-content">
                    <!-- Results table will be shown here -->
                </div>

                <div class="querymind-sql-display" id="querymind-sql-display">
                    <details>
                        <summary><?php esc_html_e( 'View SQL Query', 'querymind' ); ?></summary>
                        <pre id="querymind-sql-code"></pre>
                    </details>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Save Query Modal -->
<div id="querymind-save-modal" class="querymind-modal" style="display: none;">
    <div class="querymind-modal-content">
        <div class="querymind-modal-header">
            <h3><?php esc_html_e( 'Save Query', 'querymind' ); ?></h3>
            <button class="querymind-modal-close">&times;</button>
        </div>
        <div class="querymind-modal-body">
            <label for="querymind-save-name"><?php esc_html_e( 'Query Name', 'querymind' ); ?></label>
            <input type="text" id="querymind-save-name" placeholder="<?php esc_attr_e( 'Enter a name for this query...', 'querymind' ); ?>">
        </div>
        <div class="querymind-modal-footer">
            <button class="button" id="querymind-save-cancel"><?php esc_html_e( 'Cancel', 'querymind' ); ?></button>
            <button class="button button-primary" id="querymind-save-confirm"><?php esc_html_e( 'Save', 'querymind' ); ?></button>
        </div>
    </div>
</div>
