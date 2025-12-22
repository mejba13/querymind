<?php
/**
 * REST Controller
 *
 * Handles REST API endpoints for QueryMind.
 *
 * @package QueryMind
 */

namespace QueryMind\Api;

use QueryMind\Core\SchemaDiscovery;
use QueryMind\Core\QueryExecutor;
use QueryMind\Core\QueryValidator;
use QueryMind\AI\AIRouter;
use QueryMind\AI\ProviderException;
use QueryMind\Utils\Sanitizer;

/**
 * REST Controller class.
 *
 * Registers and handles all REST API endpoints.
 */
class RestController {

    /**
     * Namespace for API routes.
     */
    private const NAMESPACE = 'querymind/v1';

    /**
     * Schema Discovery instance.
     *
     * @var SchemaDiscovery
     */
    private SchemaDiscovery $schema_discovery;

    /**
     * Query Executor instance.
     *
     * @var QueryExecutor
     */
    private QueryExecutor $executor;

    /**
     * AI Router instance.
     *
     * @var AIRouter
     */
    private AIRouter $ai_router;

    /**
     * Sanitizer instance.
     *
     * @var Sanitizer
     */
    private Sanitizer $sanitizer;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->schema_discovery = new SchemaDiscovery();
        $this->executor = new QueryExecutor();
        $this->ai_router = new AIRouter();
        $this->sanitizer = new Sanitizer();
    }

    /**
     * Register REST routes.
     */
    public function register_routes(): void {
        // Query endpoint
        register_rest_route( self::NAMESPACE, '/query', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_query' ],
            'permission_callback' => [ $this, 'check_query_permission' ],
            'args'                => [
                'question' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ] );

        // Execute raw SQL (for saved queries)
        register_rest_route( self::NAMESPACE, '/execute', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_execute' ],
            'permission_callback' => [ $this, 'check_query_permission' ],
            'args'                => [
                'sql' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ] );

        // Get schema
        register_rest_route( self::NAMESPACE, '/schema', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_schema' ],
            'permission_callback' => [ $this, 'check_query_permission' ],
        ] );

        // Get suggestions
        register_rest_route( self::NAMESPACE, '/suggestions', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_suggestions' ],
            'permission_callback' => [ $this, 'check_query_permission' ],
        ] );

        // Query history
        register_rest_route( self::NAMESPACE, '/history', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_history' ],
            'permission_callback' => [ $this, 'check_query_permission' ],
            'args'                => [
                'page'     => [
                    'default' => 1,
                    'type'    => 'integer',
                ],
                'per_page' => [
                    'default' => 20,
                    'type'    => 'integer',
                ],
            ],
        ] );

        // Saved queries
        register_rest_route( self::NAMESPACE, '/saved', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_saved_queries' ],
            'permission_callback' => [ $this, 'check_query_permission' ],
        ] );

        register_rest_route( self::NAMESPACE, '/saved', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'save_query' ],
            'permission_callback' => [ $this, 'check_query_permission' ],
            'args'                => [
                'name'     => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'question' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'sql'      => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/saved/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'delete_saved_query' ],
            'permission_callback' => [ $this, 'check_query_permission' ],
        ] );

        // Settings (admin only)
        register_rest_route( self::NAMESPACE, '/settings', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_settings' ],
            'permission_callback' => [ $this, 'check_admin_permission' ],
        ] );

        register_rest_route( self::NAMESPACE, '/settings', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'update_settings' ],
            'permission_callback' => [ $this, 'check_admin_permission' ],
        ] );

        // Provider status
        register_rest_route( self::NAMESPACE, '/providers', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_providers' ],
            'permission_callback' => [ $this, 'check_admin_permission' ],
        ] );

        // Export results
        register_rest_route( self::NAMESPACE, '/export', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'export_results' ],
            'permission_callback' => [ $this, 'check_query_permission' ],
            'args'                => [
                'data'   => [
                    'required' => true,
                    'type'     => 'array',
                ],
                'format' => [
                    'default' => 'csv',
                    'type'    => 'string',
                    'enum'    => [ 'csv', 'json' ],
                ],
            ],
        ] );
    }

    /**
     * Check if user can perform queries.
     *
     * @return bool|\WP_Error
     */
    public function check_query_permission() {
        if ( ! is_user_logged_in() ) {
            return new \WP_Error(
                'rest_not_logged_in',
                __( 'You must be logged in to use QueryMind.', 'querymind' ),
                [ 'status' => 401 ]
            );
        }

        $allowed_roles = get_option( 'querymind_allowed_roles', [ 'administrator' ] );
        $user = wp_get_current_user();

        foreach ( $allowed_roles as $role ) {
            if ( in_array( $role, $user->roles, true ) ) {
                return true;
            }
        }

        return new \WP_Error(
            'rest_forbidden',
            __( 'You do not have permission to use QueryMind.', 'querymind' ),
            [ 'status' => 403 ]
        );
    }

    /**
     * Check admin permission.
     *
     * @return bool|\WP_Error
     */
    public function check_admin_permission() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new \WP_Error(
                'rest_forbidden',
                __( 'You must be an administrator to access this.', 'querymind' ),
                [ 'status' => 403 ]
            );
        }
        return true;
    }

    /**
     * Handle query request.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_query( \WP_REST_Request $request ) {
        $question = $request->get_param( 'question' );

        // Check daily limit
        if ( ! $this->check_daily_limit() ) {
            return new \WP_Error(
                'limit_exceeded',
                __( 'Daily query limit exceeded. Please try again tomorrow or upgrade your plan.', 'querymind' ),
                [ 'status' => 429 ]
            );
        }

        // Sanitize the question
        $sanitize_result = $this->sanitizer->sanitize_question( $question );
        if ( ! $sanitize_result->success ) {
            return new \WP_Error(
                'invalid_question',
                $sanitize_result->error,
                [ 'status' => 400 ]
            );
        }
        $question = $sanitize_result->question;

        // Process the query
        $result = $this->process_query( $question );

        if ( ! $result['success'] ) {
            return new \WP_Error(
                'query_failed',
                $result['message'] ?? __( 'Query failed', 'querymind' ),
                [ 'status' => 400 ]
            );
        }

        // Log the query
        $this->log_query( $question, $result );

        return rest_ensure_response( $result );
    }

    /**
     * Process a natural language query.
     *
     * @param string $question User's question.
     * @return array
     */
    public function process_query( string $question ): array {
        // Check if AI is configured
        if ( ! $this->ai_router->has_configured_provider() ) {
            return [
                'success' => false,
                'message' => __( 'No AI provider configured. Please add an API key in settings.', 'querymind' ),
            ];
        }

        // Get schema
        $schema = $this->schema_discovery->get_schema();
        $integrations = $this->schema_discovery->get_detected_integrations();

        try {
            // Generate SQL
            $sql_result = $this->ai_router->generate_sql( $question, $schema, [
                'integrations' => $integrations,
            ] );

            // Execute the query
            $execution_result = $this->executor->execute( $sql_result->sql );

            if ( ! $execution_result->success ) {
                return [
                    'success'     => false,
                    'message'     => implode( ', ', $execution_result->errors ),
                    'sql'         => $sql_result->sql,
                    'explanation' => $sql_result->explanation,
                ];
            }

            return [
                'success'        => true,
                'data'           => $execution_result->data,
                'columns'        => $execution_result->columns,
                'row_count'      => $execution_result->row_count,
                'execution_time' => $execution_result->execution_time,
                'sql'            => $sql_result->sql,
                'explanation'    => $sql_result->explanation,
                'chart_type'     => $sql_result->chart_type,
                'warnings'       => $execution_result->warnings,
            ];

        } catch ( ProviderException $e ) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'code'    => $e->get_provider_code(),
            ];
        } catch ( \Exception $e ) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle direct SQL execution.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_execute( \WP_REST_Request $request ) {
        $sql = $request->get_param( 'sql' );

        $result = $this->executor->execute( $sql );

        if ( ! $result->success ) {
            return new \WP_Error(
                'execution_failed',
                implode( ', ', $result->errors ),
                [ 'status' => 400 ]
            );
        }

        return rest_ensure_response( $result->to_array() );
    }

    /**
     * Get database schema.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_schema( \WP_REST_Request $request ) {
        $schema = $this->schema_discovery->get_schema();
        $integrations = $this->schema_discovery->get_detected_integrations();

        return rest_ensure_response( [
            'schema'       => $schema,
            'integrations' => $integrations,
            'table_count'  => count( $schema ),
        ] );
    }

    /**
     * Get query suggestions.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_suggestions( \WP_REST_Request $request ) {
        $querymind = \QueryMind\QueryMind::get_instance();
        $suggestions = $querymind->get_suggestions();

        return rest_ensure_response( [
            'suggestions' => $suggestions,
        ] );
    }

    /**
     * Get query history.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_history( \WP_REST_Request $request ) {
        global $wpdb;

        $page = absint( $request->get_param( 'page' ) );
        $per_page = min( absint( $request->get_param( 'per_page' ) ), 100 );
        $offset = ( $page - 1 ) * $per_page;
        $user_id = get_current_user_id();

        $table = $wpdb->prefix . 'querymind_history';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $history = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $user_id,
                $per_page,
                $offset
            )
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
                $user_id
            )
        );

        return rest_ensure_response( [
            'history'    => $history,
            'total'      => (int) $total,
            'page'       => $page,
            'per_page'   => $per_page,
            'total_pages' => ceil( $total / $per_page ),
        ] );
    }

    /**
     * Get saved queries.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_saved_queries( \WP_REST_Request $request ) {
        global $wpdb;

        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'querymind_saved';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $saved = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d ORDER BY is_favorite DESC, updated_at DESC",
                $user_id
            )
        );

        return rest_ensure_response( [
            'saved' => $saved,
        ] );
    }

    /**
     * Save a query.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function save_query( \WP_REST_Request $request ) {
        global $wpdb;

        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'querymind_saved';

        $result = $wpdb->insert(
            $table,
            [
                'user_id'    => $user_id,
                'name'       => $request->get_param( 'name' ),
                'question'   => $request->get_param( 'question' ),
                'sql_query'  => $request->get_param( 'sql' ),
                'chart_type' => $request->get_param( 'chart_type' ) ?? 'table',
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( $result === false ) {
            return new \WP_Error(
                'save_failed',
                __( 'Failed to save query.', 'querymind' ),
                [ 'status' => 500 ]
            );
        }

        return rest_ensure_response( [
            'success' => true,
            'id'      => $wpdb->insert_id,
        ] );
    }

    /**
     * Delete a saved query.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function delete_saved_query( \WP_REST_Request $request ) {
        global $wpdb;

        $id = absint( $request->get_param( 'id' ) );
        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'querymind_saved';

        $result = $wpdb->delete(
            $table,
            [
                'id'      => $id,
                'user_id' => $user_id,
            ],
            [ '%d', '%d' ]
        );

        if ( $result === false ) {
            return new \WP_Error(
                'delete_failed',
                __( 'Failed to delete query.', 'querymind' ),
                [ 'status' => 500 ]
            );
        }

        return rest_ensure_response( [
            'success' => true,
        ] );
    }

    /**
     * Get settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_settings( \WP_REST_Request $request ) {
        return rest_ensure_response( [
            'ai_provider'    => get_option( 'querymind_ai_provider', 'openai' ),
            'ai_model'       => get_option( 'querymind_ai_model', 'gpt-4o-mini' ),
            'max_rows'       => get_option( 'querymind_max_rows', 1000 ),
            'query_timeout'  => get_option( 'querymind_query_timeout', 30 ),
            'daily_limit'    => get_option( 'querymind_daily_limit', 20 ),
            'enable_cache'   => get_option( 'querymind_enable_cache', true ),
            'cache_duration' => get_option( 'querymind_cache_duration', 3600 ),
            'allowed_roles'  => get_option( 'querymind_allowed_roles', [ 'administrator' ] ),
            'has_openai_key' => ! empty( get_option( 'querymind_openai_key' ) ),
            'has_anthropic_key' => ! empty( get_option( 'querymind_anthropic_key' ) ),
        ] );
    }

    /**
     * Update settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function update_settings( \WP_REST_Request $request ) {
        $settings = $request->get_json_params();

        $allowed_settings = [
            'ai_provider',
            'ai_model',
            'openai_key',
            'anthropic_key',
            'max_rows',
            'query_timeout',
            'daily_limit',
            'enable_cache',
            'cache_duration',
            'allowed_roles',
        ];

        foreach ( $allowed_settings as $key ) {
            if ( isset( $settings[ $key ] ) ) {
                update_option( 'querymind_' . $key, $settings[ $key ] );
            }
        }

        // Clear schema cache if settings changed
        $this->schema_discovery->clear_cache();

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Settings saved successfully.', 'querymind' ),
        ] );
    }

    /**
     * Get AI providers status.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_providers( \WP_REST_Request $request ) {
        $providers = $this->ai_router->get_available_providers();

        return rest_ensure_response( [
            'providers'       => $providers,
            'default'         => get_option( 'querymind_ai_provider', 'openai' ),
            'has_any_configured' => $this->ai_router->has_configured_provider(),
        ] );
    }

    /**
     * Export results.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function export_results( \WP_REST_Request $request ) {
        $data = $request->get_param( 'data' );
        $format = $request->get_param( 'format' );

        if ( empty( $data ) ) {
            return new \WP_Error(
                'no_data',
                __( 'No data to export.', 'querymind' ),
                [ 'status' => 400 ]
            );
        }

        if ( $format === 'json' ) {
            return rest_ensure_response( [
                'content'  => wp_json_encode( $data, JSON_PRETTY_PRINT ),
                'filename' => 'querymind-export-' . gmdate( 'Y-m-d-His' ) . '.json',
                'type'     => 'application/json',
            ] );
        }

        // CSV format
        $csv = $this->array_to_csv( $data );

        return rest_ensure_response( [
            'content'  => $csv,
            'filename' => 'querymind-export-' . gmdate( 'Y-m-d-His' ) . '.csv',
            'type'     => 'text/csv',
        ] );
    }

    /**
     * Convert array to CSV.
     *
     * @param array $data Data array.
     * @return string
     */
    private function array_to_csv( array $data ): string {
        if ( empty( $data ) ) {
            return '';
        }

        $output = fopen( 'php://temp', 'r+' );

        // Write header
        fputcsv( $output, array_keys( $data[0] ) );

        // Write rows
        foreach ( $data as $row ) {
            fputcsv( $output, $row );
        }

        rewind( $output );
        $csv = stream_get_contents( $output );
        fclose( $output );

        return $csv;
    }

    /**
     * Check daily query limit.
     *
     * @return bool
     */
    private function check_daily_limit(): bool {
        $limit = absint( get_option( 'querymind_daily_limit', 20 ) );
        if ( $limit <= 0 ) {
            return true; // Unlimited
        }

        $user_id = get_current_user_id();
        $today = gmdate( 'Y-m-d' );
        $cache_key = "querymind_daily_count_{$user_id}_{$today}";

        $count = (int) get_transient( $cache_key );

        return $count < $limit;
    }

    /**
     * Log a query to history.
     *
     * @param string $question Question asked.
     * @param array  $result   Query result.
     */
    private function log_query( string $question, array $result ): void {
        global $wpdb;

        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'querymind_history';

        $wpdb->insert(
            $table,
            [
                'user_id'        => $user_id,
                'question'       => $question,
                'generated_sql'  => $result['sql'] ?? '',
                'execution_time' => $result['execution_time'] ?? null,
                'row_count'      => $result['row_count'] ?? null,
                'status'         => $result['success'] ? 'success' : 'error',
                'error_message'  => $result['success'] ? null : ( $result['message'] ?? null ),
                'created_at'     => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s' ]
        );

        // Update daily count
        $today = gmdate( 'Y-m-d' );
        $cache_key = "querymind_daily_count_{$user_id}_{$today}";
        $count = (int) get_transient( $cache_key );
        set_transient( $cache_key, $count + 1, DAY_IN_SECONDS );
    }
}
