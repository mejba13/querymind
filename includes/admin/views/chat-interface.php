<?php
/**
 * Chat Interface View - Query Data Page
 *
 * @package QueryMind
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$has_api_key = ! empty( get_option( 'querymind_openai_key' ) ) || ! empty( get_option( 'querymind_anthropic_key' ) );
?>
<div class="wrap querymind-wrap">
    <!-- Page Header -->
    <header class="querymind-page-header">
        <div class="querymind-page-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                <path d="M3 5V19A9 3 0 0 0 21 19V5"></path>
                <path d="M3 12A9 3 0 0 0 21 12"></path>
            </svg>
        </div>
        <div>
            <h1 class="querymind-page-title"><?php esc_html_e( 'QueryMind', 'querymind' ); ?></h1>
            <p class="querymind-page-subtitle"><?php esc_html_e( 'Ask questions about your WordPress data in plain English', 'querymind' ); ?></p>
        </div>
    </header>

    <div id="querymind-app" class="querymind-container">
        <!-- NoScript fallback -->
        <noscript>
            <div class="querymind-notice querymind-notice-warning">
                <span class="dashicons dashicons-warning"></span>
                <p><?php esc_html_e( 'JavaScript is required to use QueryMind.', 'querymind' ); ?></p>
            </div>
        </noscript>

        <!-- Main Chat Interface -->
        <div class="querymind-fallback" id="querymind-fallback">
            <div class="querymind-chat-container">
                <!-- Chat Header -->
                <div class="querymind-chat-header">
                    <h2><?php esc_html_e( 'Ask Your Data', 'querymind' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e( 'Query your database using natural language. Get instant insights from posts, users, orders, and more.', 'querymind' ); ?>
                    </p>
                </div>

                <!-- API Key Warning -->
                <?php if ( ! $has_api_key ) : ?>
                    <div class="querymind-notice querymind-notice-warning" role="alert">
                        <span class="querymind-notice-icon">
                            <span class="dashicons dashicons-warning"></span>
                        </span>
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
                <div class="querymind-messages" id="querymind-messages" role="log" aria-live="polite" aria-label="<?php esc_attr_e( 'Query conversation', 'querymind' ); ?>">
                    <div class="querymind-welcome">
                        <div class="querymind-welcome-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.3-4.3"></path>
                                <path d="M11 8v6"></path>
                                <path d="M8 11h6"></path>
                            </svg>
                        </div>
                        <div class="querymind-suggestions">
                            <p class="suggestions-label"><?php esc_html_e( 'Try asking', 'querymind' ); ?></p>
                            <div class="suggestion-chips" id="querymind-suggestions" role="group" aria-label="<?php esc_attr_e( 'Suggested queries', 'querymind' ); ?>">
                                <!-- Suggestions loaded via JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Container -->
                <div class="querymind-input-container">
                    <form id="querymind-form" class="querymind-form" role="search">
                        <div class="querymind-input-wrapper">
                            <textarea
                                id="querymind-input"
                                class="querymind-input"
                                placeholder="<?php esc_attr_e( 'Ask a question about your data...', 'querymind' ); ?>"
                                rows="1"
                                aria-label="<?php esc_attr_e( 'Query input', 'querymind' ); ?>"
                                <?php echo ! $has_api_key ? 'disabled' : ''; ?>
                            ></textarea>
                            <button
                                type="submit"
                                class="querymind-submit"
                                id="querymind-submit"
                                disabled
                                aria-label="<?php esc_attr_e( 'Submit query', 'querymind' ); ?>"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14"></path>
                                    <path d="m12 5 7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                    <p class="querymind-input-hint">
                        <kbd>Enter</kbd> <?php esc_html_e( 'to submit', 'querymind' ); ?> &bull;
                        <kbd>Shift</kbd> + <kbd>Enter</kbd> <?php esc_html_e( 'for new line', 'querymind' ); ?>
                    </p>
                </div>
            </div>

            <!-- Results Panel -->
            <div class="querymind-results-panel" id="querymind-results" style="display: none;" aria-live="polite">
                <div class="querymind-results-header">
                    <h3 id="querymind-results-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; vertical-align: -4px; color: var(--qm-olive-leaf);">
                            <path d="M3 3v18h18"></path>
                            <path d="m19 9-5 5-4-4-3 3"></path>
                        </svg>
                        <?php esc_html_e( 'Results', 'querymind' ); ?>
                    </h3>
                    <div class="querymind-results-actions">
                        <button class="button querymind-btn-outline querymind-btn-sm" id="querymind-export-csv" aria-label="<?php esc_attr_e( 'Export results as CSV', 'querymind' ); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" x2="12" y1="15" y2="3"></line>
                            </svg>
                            <?php esc_html_e( 'Export CSV', 'querymind' ); ?>
                        </button>
                        <button class="button button-primary querymind-btn-sm" id="querymind-save-query" aria-label="<?php esc_attr_e( 'Save this query', 'querymind' ); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                            <?php esc_html_e( 'Save Query', 'querymind' ); ?>
                        </button>
                    </div>
                </div>

                <div class="querymind-results-meta" id="querymind-results-meta">
                    <!-- Meta info populated via JS -->
                </div>

                <div class="querymind-results-content" id="querymind-results-content">
                    <!-- Results table populated via JS -->
                </div>

                <div class="querymind-sql-display" id="querymind-sql-display">
                    <details>
                        <summary>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -3px; margin-right: 6px;">
                                <polyline points="16 18 22 12 16 6"></polyline>
                                <polyline points="8 6 2 12 8 18"></polyline>
                            </svg>
                            <?php esc_html_e( 'View SQL Query', 'querymind' ); ?>
                        </summary>
                        <pre id="querymind-sql-code" aria-label="<?php esc_attr_e( 'Generated SQL query', 'querymind' ); ?>"></pre>
                    </details>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Save Query Modal -->
<div id="querymind-save-modal" class="querymind-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="save-modal-title">
    <div class="querymind-modal-content">
        <div class="querymind-modal-header">
            <h3 id="save-modal-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; vertical-align: -4px; color: var(--qm-sunlit-clay);">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                <?php esc_html_e( 'Save Query', 'querymind' ); ?>
            </h3>
            <button class="querymind-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'querymind' ); ?>">&times;</button>
        </div>
        <div class="querymind-modal-body">
            <label for="querymind-save-name"><?php esc_html_e( 'Query Name', 'querymind' ); ?></label>
            <input
                type="text"
                id="querymind-save-name"
                placeholder="<?php esc_attr_e( 'Enter a memorable name for this query...', 'querymind' ); ?>"
                autocomplete="off"
            >
            <p class="description" style="margin-top: 8px; color: var(--qm-text-muted); font-size: 0.8125rem;">
                <?php esc_html_e( 'Give your query a descriptive name so you can easily find it later.', 'querymind' ); ?>
            </p>
        </div>
        <div class="querymind-modal-footer">
            <button class="button" id="querymind-save-cancel"><?php esc_html_e( 'Cancel', 'querymind' ); ?></button>
            <button class="button button-primary" id="querymind-save-confirm">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: -3px;">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <?php esc_html_e( 'Save Query', 'querymind' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="querymind-toast-container" class="querymind-toast-container" aria-live="polite" aria-label="<?php esc_attr_e( 'Notifications', 'querymind' ); ?>"></div>
