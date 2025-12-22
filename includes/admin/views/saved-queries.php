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
$table = $wpdb->prefix . 'querymind_saved';
$user_id = get_current_user_id();

// Get saved queries
$saved_queries = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table} WHERE user_id = %d ORDER BY is_favorite DESC, updated_at DESC",
        $user_id
    )
);
?>
<div class="wrap querymind-wrap">
    <h1>
        <span class="dashicons dashicons-star-filled"></span>
        <?php esc_html_e( 'Saved Queries', 'querymind' ); ?>
    </h1>

    <?php if ( empty( $saved_queries ) ) : ?>
        <div class="querymind-empty-state">
            <span class="dashicons dashicons-star-empty"></span>
            <h2><?php esc_html_e( 'No saved queries', 'querymind' ); ?></h2>
            <p><?php esc_html_e( 'Save your frequently used queries for quick access.', 'querymind' ); ?></p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=querymind' ) ); ?>" class="button button-primary">
                <?php esc_html_e( 'Start Querying', 'querymind' ); ?>
            </a>
        </div>
    <?php else : ?>
        <div class="querymind-saved-grid">
            <?php foreach ( $saved_queries as $query ) : ?>
                <div class="querymind-saved-card" data-id="<?php echo esc_attr( $query->id ); ?>">
                    <div class="querymind-saved-card-header">
                        <h3>
                            <?php if ( $query->is_favorite ) : ?>
                                <span class="dashicons dashicons-star-filled querymind-favorite"></span>
                            <?php endif; ?>
                            <?php echo esc_html( $query->name ); ?>
                        </h3>
                        <div class="querymind-saved-card-actions">
                            <button class="querymind-toggle-favorite" title="<?php esc_attr_e( 'Toggle favorite', 'querymind' ); ?>">
                                <span class="dashicons dashicons-<?php echo $query->is_favorite ? 'star-filled' : 'star-empty'; ?>"></span>
                            </button>
                            <button class="querymind-delete-saved" title="<?php esc_attr_e( 'Delete', 'querymind' ); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                    <div class="querymind-saved-card-body">
                        <p class="querymind-saved-question"><?php echo esc_html( $query->question ); ?></p>
                        <div class="querymind-saved-meta">
                            <span class="querymind-chart-type">
                                <span class="dashicons dashicons-chart-<?php echo esc_attr( $query->chart_type === 'table' ? 'bar' : $query->chart_type ); ?>"></span>
                                <?php echo esc_html( ucfirst( $query->chart_type ) ); ?>
                            </span>
                            <span class="querymind-saved-date">
                                <?php echo esc_html( human_time_diff( strtotime( $query->created_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'querymind' ) ); ?>
                            </span>
                        </div>
                    </div>
                    <div class="querymind-saved-card-footer">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=querymind&query=' . urlencode( $query->question ) ) ); ?>" class="button button-primary">
                            <span class="dashicons dashicons-controls-play"></span>
                            <?php esc_html_e( 'Run Query', 'querymind' ); ?>
                        </a>
                        <button class="button querymind-view-sql" data-sql="<?php echo esc_attr( $query->sql_query ); ?>">
                            <span class="dashicons dashicons-editor-code"></span>
                            <?php esc_html_e( 'View SQL', 'querymind' ); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- SQL Modal -->
        <div id="querymind-sql-modal" class="querymind-modal" style="display: none;">
            <div class="querymind-modal-content">
                <div class="querymind-modal-header">
                    <h3><?php esc_html_e( 'SQL Query', 'querymind' ); ?></h3>
                    <button class="querymind-modal-close">&times;</button>
                </div>
                <div class="querymind-modal-body">
                    <pre id="querymind-modal-sql-code"></pre>
                </div>
                <div class="querymind-modal-footer">
                    <button class="button" id="querymind-copy-sql">
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php esc_html_e( 'Copy SQL', 'querymind' ); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
