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
$table   = $wpdb->prefix . 'querymind_history';
$user_id = get_current_user_id();

// Pagination
$per_page     = 20;
$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$offset       = ( $current_page - 1 ) * $per_page;

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
    <!-- Page Header -->
    <header class="querymind-page-header">
        <div class="querymind-page-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
        <div>
            <h1 class="querymind-page-title"><?php esc_html_e( 'Query History', 'querymind' ); ?></h1>
            <p class="querymind-page-subtitle">
                <?php
                printf(
                    /* translators: %s: total number of queries */
                    esc_html( _n( '%s query executed', '%s queries executed', $total, 'querymind' ) ),
                    esc_html( number_format_i18n( $total ) )
                );
                ?>
            </p>
        </div>
    </header>

    <?php if ( empty( $history ) ) : ?>
        <div class="querymind-empty-state">
            <div class="querymind-empty-state-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--qm-olive-leaf);">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <h2><?php esc_html_e( 'No queries yet', 'querymind' ); ?></h2>
            <p><?php esc_html_e( 'Your query history will appear here once you start asking questions about your data.', 'querymind' ); ?></p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=querymind' ) ); ?>" class="button button-primary button-hero">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; vertical-align: -4px;">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.3-4.3"></path>
                </svg>
                <?php esc_html_e( 'Start Querying', 'querymind' ); ?>
            </a>
        </div>
    <?php else : ?>
        <div class="querymind-history-list">
            <table class="wp-list-table widefat fixed striped" role="table">
                <thead>
                    <tr>
                        <th scope="col" class="column-question" style="width: 40%;">
                            <span class="dashicons dashicons-editor-help" style="font-size: 16px; width: 16px; height: 16px; vertical-align: -3px; margin-right: 4px; color: var(--qm-olive-leaf);"></span>
                            <?php esc_html_e( 'Question', 'querymind' ); ?>
                        </th>
                        <th scope="col" class="column-status" style="width: 10%;">
                            <?php esc_html_e( 'Status', 'querymind' ); ?>
                        </th>
                        <th scope="col" class="column-rows" style="width: 10%;">
                            <span class="dashicons dashicons-editor-table" style="font-size: 16px; width: 16px; height: 16px; vertical-align: -3px; margin-right: 4px; color: var(--qm-olive-leaf);"></span>
                            <?php esc_html_e( 'Rows', 'querymind' ); ?>
                        </th>
                        <th scope="col" class="column-time" style="width: 12%;">
                            <span class="dashicons dashicons-performance" style="font-size: 16px; width: 16px; height: 16px; vertical-align: -3px; margin-right: 4px; color: var(--qm-olive-leaf);"></span>
                            <?php esc_html_e( 'Time', 'querymind' ); ?>
                        </th>
                        <th scope="col" class="column-date" style="width: 13%;">
                            <span class="dashicons dashicons-calendar-alt" style="font-size: 16px; width: 16px; height: 16px; vertical-align: -3px; margin-right: 4px; color: var(--qm-olive-leaf);"></span>
                            <?php esc_html_e( 'Date', 'querymind' ); ?>
                        </th>
                        <th scope="col" class="column-actions" style="width: 15%;">
                            <?php esc_html_e( 'Actions', 'querymind' ); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $history as $item ) : ?>
                        <tr>
                            <td class="column-question">
                                <strong><?php echo esc_html( wp_trim_words( $item->question, 12 ) ); ?></strong>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="#" class="querymind-view-sql" data-sql="<?php echo esc_attr( $item->generated_sql ); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 4px;">
                                                <polyline points="16 18 22 12 16 6"></polyline>
                                                <polyline points="8 6 2 12 8 18"></polyline>
                                            </svg>
                                            <?php esc_html_e( 'View SQL', 'querymind' ); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td class="column-status">
                                <?php if ( 'success' === $item->status ) : ?>
                                    <span class="querymind-badge querymind-badge-success">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 2px;">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        <?php esc_html_e( 'Success', 'querymind' ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="querymind-badge querymind-badge-error" title="<?php echo esc_attr( $item->error_message ); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 2px;">
                                            <path d="M18 6 6 18"></path>
                                            <path d="m6 6 12 12"></path>
                                        </svg>
                                        <?php esc_html_e( 'Error', 'querymind' ); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="column-rows">
                                <?php if ( null !== $item->row_count ) : ?>
                                    <span style="font-weight: 600; color: var(--qm-text-primary);">
                                        <?php echo esc_html( number_format( $item->row_count ) ); ?>
                                    </span>
                                <?php else : ?>
                                    <span style="color: var(--qm-text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-time">
                                <?php if ( null !== $item->execution_time ) : ?>
                                    <span style="font-family: var(--qm-font-mono); font-size: 0.8125rem;">
                                        <?php echo esc_html( number_format( $item->execution_time, 3 ) ); ?>s
                                    </span>
                                <?php else : ?>
                                    <span style="color: var(--qm-text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-date">
                                <span title="<?php echo esc_attr( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item->created_at ) ) ); ?>">
                                    <?php echo esc_html( human_time_diff( strtotime( $item->created_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'querymind' ) ); ?>
                                </span>
                            </td>
                            <td class="column-actions">
                                <a href="#" class="button button-small querymind-rerun" data-question="<?php echo esc_attr( $item->question ); ?>" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"></path>
                                        <path d="M21 3v5h-5"></path>
                                    </svg>
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
                        $pagination = paginate_links(
                            [
                                'base'      => add_query_arg( 'paged', '%#%' ),
                                'format'    => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total'     => $total_pages,
                                'current'   => $current_page,
                            ]
                        );
                        echo wp_kses_post( $pagination );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
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
                        <?php esc_html_e( 'Generated SQL Query', 'querymind' ); ?>
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
    <?php endif; ?>
</div>

<!-- Toast Container -->
<div id="querymind-toast-container" class="querymind-toast-container" aria-live="polite" aria-label="<?php esc_attr_e( 'Notifications', 'querymind' ); ?>"></div>
