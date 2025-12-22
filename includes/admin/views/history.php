<?php
/**
 * History View
 *
 * @package QueryMind
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'querymind_history';
$user_id = get_current_user_id();

// Pagination
$per_page = 20;
$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$offset = ( $current_page - 1 ) * $per_page;

// Get total count
$total = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
        $user_id
    )
);

// Get history items
$history = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $user_id,
        $per_page,
        $offset
    )
);

$total_pages = ceil( $total / $per_page );
?>
<div class="wrap querymind-wrap">
    <h1>
        <span class="dashicons dashicons-backup"></span>
        <?php esc_html_e( 'Query History', 'querymind' ); ?>
    </h1>

    <?php if ( empty( $history ) ) : ?>
        <div class="querymind-empty-state">
            <span class="dashicons dashicons-clock"></span>
            <h2><?php esc_html_e( 'No queries yet', 'querymind' ); ?></h2>
            <p><?php esc_html_e( 'Your query history will appear here once you start asking questions.', 'querymind' ); ?></p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=querymind' ) ); ?>" class="button button-primary">
                <?php esc_html_e( 'Start Querying', 'querymind' ); ?>
            </a>
        </div>
    <?php else : ?>
        <div class="querymind-history-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="column-question"><?php esc_html_e( 'Question', 'querymind' ); ?></th>
                        <th class="column-status"><?php esc_html_e( 'Status', 'querymind' ); ?></th>
                        <th class="column-rows"><?php esc_html_e( 'Rows', 'querymind' ); ?></th>
                        <th class="column-time"><?php esc_html_e( 'Time', 'querymind' ); ?></th>
                        <th class="column-date"><?php esc_html_e( 'Date', 'querymind' ); ?></th>
                        <th class="column-actions"><?php esc_html_e( 'Actions', 'querymind' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $history as $item ) : ?>
                        <tr>
                            <td class="column-question">
                                <strong><?php echo esc_html( wp_trim_words( $item->question, 15 ) ); ?></strong>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="#" class="querymind-view-sql" data-sql="<?php echo esc_attr( $item->generated_sql ); ?>">
                                            <?php esc_html_e( 'View SQL', 'querymind' ); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td class="column-status">
                                <?php if ( $item->status === 'success' ) : ?>
                                    <span class="querymind-badge querymind-badge-success">
                                        <?php esc_html_e( 'Success', 'querymind' ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="querymind-badge querymind-badge-error" title="<?php echo esc_attr( $item->error_message ); ?>">
                                        <?php esc_html_e( 'Error', 'querymind' ); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="column-rows">
                                <?php echo $item->row_count !== null ? esc_html( number_format( $item->row_count ) ) : '-'; ?>
                            </td>
                            <td class="column-time">
                                <?php echo $item->execution_time !== null ? esc_html( number_format( $item->execution_time, 4 ) . 's' ) : '-'; ?>
                            </td>
                            <td class="column-date">
                                <?php echo esc_html( human_time_diff( strtotime( $item->created_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'querymind' ) ); ?>
                            </td>
                            <td class="column-actions">
                                <a href="#" class="button button-small querymind-rerun" data-question="<?php echo esc_attr( $item->question ); ?>">
                                    <span class="dashicons dashicons-controls-repeat"></span>
                                    <?php esc_html_e( 'Run Again', 'querymind' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ( $total_pages > 1 ) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links( [
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_pages,
                            'current'   => $current_page,
                        ] );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
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
