<?php
/**
 * Saved Queries View
 *
 * @package QueryMind
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table   = $wpdb->prefix . 'querymind_saved';
$user_id = get_current_user_id();

// Get saved queries
$saved_queries = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table} WHERE user_id = %d ORDER BY is_favorite DESC, updated_at DESC",
        $user_id
    )
);

$total_saved     = count( $saved_queries );
$total_favorites = count( array_filter( $saved_queries, function ( $q ) {
    return $q->is_favorite;
} ) );
?>
<div class="wrap querymind-wrap">
    <!-- Page Header -->
    <header class="querymind-page-header">
        <div class="querymind-page-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
        </div>
        <div>
            <h1 class="querymind-page-title"><?php esc_html_e( 'Saved Queries', 'querymind' ); ?></h1>
            <p class="querymind-page-subtitle">
                <?php
                if ( $total_saved > 0 ) {
                    printf(
                        /* translators: 1: total saved queries, 2: favorite count */
                        esc_html__( '%1$d saved queries, %2$d favorites', 'querymind' ),
                        $total_saved,
                        $total_favorites
                    );
                } else {
                    esc_html_e( 'Save your frequently used queries for quick access', 'querymind' );
                }
                ?>
            </p>
        </div>
    </header>

    <?php if ( empty( $saved_queries ) ) : ?>
        <div class="querymind-empty-state">
            <div class="querymind-empty-state-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--qm-olive-leaf);">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
            </div>
            <h2><?php esc_html_e( 'No saved queries yet', 'querymind' ); ?></h2>
            <p><?php esc_html_e( 'Save your frequently used queries for quick access. Click the star button after running any query to save it here.', 'querymind' ); ?></p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=querymind' ) ); ?>" class="button button-primary button-hero">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; vertical-align: -4px;">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.3-4.3"></path>
                </svg>
                <?php esc_html_e( 'Start Querying', 'querymind' ); ?>
            </a>
        </div>
    <?php else : ?>
        <div class="querymind-saved-grid">
            <?php foreach ( $saved_queries as $query ) : ?>
                <article class="querymind-saved-card querymind-card-elevated" data-id="<?php echo esc_attr( $query->id ); ?>">
                    <div class="querymind-saved-card-header">
                        <h3>
                            <?php if ( $query->is_favorite ) : ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="var(--qm-sunlit-clay)" stroke="var(--qm-sunlit-clay)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="querymind-favorite">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                            <?php endif; ?>
                            <?php echo esc_html( $query->name ); ?>
                        </h3>
                        <div class="querymind-saved-card-actions">
                            <button
                                class="querymind-toggle-favorite"
                                title="<?php esc_attr_e( 'Toggle favorite', 'querymind' ); ?>"
                                aria-label="<?php echo $query->is_favorite ? esc_attr__( 'Remove from favorites', 'querymind' ) : esc_attr__( 'Add to favorites', 'querymind' ); ?>"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $query->is_favorite ? 'var(--qm-sunlit-clay)' : 'none'; ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                            </button>
                            <button
                                class="querymind-delete-saved"
                                title="<?php esc_attr_e( 'Delete query', 'querymind' ); ?>"
                                aria-label="<?php esc_attr_e( 'Delete this saved query', 'querymind' ); ?>"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6h18"></path>
                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                    <line x1="10" x2="10" y1="11" y2="17"></line>
                                    <line x1="14" x2="14" y1="11" y2="17"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="querymind-saved-card-body">
                        <p class="querymind-saved-question"><?php echo esc_html( $query->question ); ?></p>
                        <div class="querymind-saved-meta">
                            <span class="querymind-chart-type">
                                <?php
                                $chart_icon = 'table' === $query->chart_type
                                    ? '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect><line x1="3" x2="21" y1="9" y2="9"></line><line x1="3" x2="21" y1="15" y2="15"></line><line x1="9" x2="9" y1="9" y2="21"></line><line x1="15" x2="15" y1="9" y2="21"></line></svg>'
                                    : '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="20" y2="10"></line><line x1="18" x2="18" y1="20" y2="4"></line><line x1="6" x2="6" y1="20" y2="14"></line></svg>';
                                echo $chart_icon;
                                ?>
                                <?php echo esc_html( ucfirst( $query->chart_type ) ); ?>
                            </span>
                            <span class="querymind-saved-date">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <?php echo esc_html( human_time_diff( strtotime( $query->created_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'querymind' ) ); ?>
                            </span>
                        </div>
                    </div>
                    <div class="querymind-saved-card-footer">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=querymind&query=' . urlencode( $query->question ) ) ); ?>" class="button button-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                            </svg>
                            <?php esc_html_e( 'Run Query', 'querymind' ); ?>
                        </a>
                        <button class="button querymind-view-sql" data-sql="<?php echo esc_attr( $query->sql_query ); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="16 18 22 12 16 6"></polyline>
                                <polyline points="8 6 2 12 8 18"></polyline>
                            </svg>
                            <?php esc_html_e( 'View SQL', 'querymind' ); ?>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- SQL Modal -->
        <div id="querymind-sql-modal" class="querymind-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="sql-modal-title">
            <div class="querymind-modal-content">
                <div class="querymind-modal-header">
                    <h3 id="sql-modal-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; vertical-align: -4px; color: var(--qm-olive-leaf);">
                            <polyline points="16 18 22 12 16 6"></polyline>
                            <polyline points="8 6 2 12 8 18"></polyline>
                        </svg>
                        <?php esc_html_e( 'SQL Query', 'querymind' ); ?>
                    </h3>
                    <button class="querymind-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'querymind' ); ?>">&times;</button>
                </div>
                <div class="querymind-modal-body">
                    <pre id="querymind-modal-sql-code" aria-label="<?php esc_attr_e( 'SQL query code', 'querymind' ); ?>"></pre>
                </div>
                <div class="querymind-modal-footer">
                    <button class="button" id="querymind-copy-sql">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: -3px;">
                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
                        </svg>
                        <?php esc_html_e( 'Copy SQL', 'querymind' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Confirm Delete Modal -->
        <div id="querymind-delete-modal" class="querymind-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
            <div class="querymind-modal-content" style="max-width: 400px;">
                <div class="querymind-modal-header">
                    <h3 id="delete-modal-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; vertical-align: -4px; color: var(--qm-error);">
                            <path d="M3 6h18"></path>
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                        </svg>
                        <?php esc_html_e( 'Delete Query', 'querymind' ); ?>
                    </h3>
                    <button class="querymind-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'querymind' ); ?>">&times;</button>
                </div>
                <div class="querymind-modal-body">
                    <p style="color: var(--qm-text-secondary); margin: 0;">
                        <?php esc_html_e( 'Are you sure you want to delete this saved query? This action cannot be undone.', 'querymind' ); ?>
                    </p>
                </div>
                <div class="querymind-modal-footer" style="justify-content: space-between;">
                    <button class="button" id="querymind-delete-cancel"><?php esc_html_e( 'Cancel', 'querymind' ); ?></button>
                    <button class="button" id="querymind-delete-confirm" style="background: var(--qm-error); border-color: var(--qm-error); color: white;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: -2px;">
                            <path d="M3 6h18"></path>
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                        </svg>
                        <?php esc_html_e( 'Delete', 'querymind' ); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Toast Container -->
<div id="querymind-toast-container" class="querymind-toast-container" aria-live="polite" aria-label="<?php esc_attr_e( 'Notifications', 'querymind' ); ?>"></div>
