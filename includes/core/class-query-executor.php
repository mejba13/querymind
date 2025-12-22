<?php
/**
 * Query Executor
 *
 * Safely executes validated SQL queries.
 *
 * @package QueryMind
 */

namespace QueryMind\Core;

/**
 * Query Executor class.
 *
 * Executes validated SQL queries with safety measures.
 */
class QueryExecutor {

    /**
     * Query Validator instance.
     *
     * @var QueryValidator
     */
    private QueryValidator $validator;

    /**
     * Maximum execution time.
     *
     * @var int
     */
    private int $max_execution_time;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->validator = new QueryValidator();
        $this->max_execution_time = $this->validator->get_max_execution_time();
    }

    /**
     * Execute a validated query.
     *
     * @param string $sql SQL query to execute.
     * @return ExecutionResult
     */
    public function execute( string $sql ): ExecutionResult {
        global $wpdb;

        // Validate first
        $validation = $this->validator->validate( $sql );

        if ( ! $validation->valid ) {
            return new ExecutionResult(
                success: false,
                errors: $validation->errors
            );
        }

        $sql = $validation->sql;

        // Start timing
        $start_time = microtime( true );

        try {
            // Set execution time limit (MySQL 5.7.8+ / MariaDB 10.1.1+)
            $timeout_ms = $this->max_execution_time * 1000;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->query( "SET SESSION MAX_EXECUTION_TIME = {$timeout_ms}" );

            // Execute query
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
            $results = $wpdb->get_results( $sql, ARRAY_A );

            // Check for errors
            if ( $wpdb->last_error ) {
                throw new \Exception( $wpdb->last_error );
            }

            $execution_time = microtime( true ) - $start_time;

            // Get column names from results or query
            $columns = [];
            if ( ! empty( $results ) && is_array( $results[0] ) ) {
                $columns = array_keys( $results[0] );
            }

            return new ExecutionResult(
                success: true,
                data: $results ?? [],
                row_count: count( $results ?? [] ),
                execution_time: round( $execution_time, 4 ),
                columns: $columns,
                warnings: $validation->warnings
            );

        } catch ( \Exception $e ) {
            return new ExecutionResult(
                success: false,
                errors: [ $e->getMessage() ]
            );
        }
    }

    /**
     * Explain query for performance analysis.
     *
     * @param string $sql SQL query to explain.
     * @return array
     */
    public function explain( string $sql ): array {
        global $wpdb;

        $validation = $this->validator->validate( $sql );
        if ( ! $validation->valid ) {
            return [ 'error' => $validation->errors ];
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $explain = $wpdb->get_results( 'EXPLAIN ' . $validation->sql, ARRAY_A );

        return [
            'plan'     => $explain,
            'warnings' => $this->analyze_explain( $explain ),
        ];
    }

    /**
     * Analyze EXPLAIN output for potential issues.
     *
     * @param array $explain EXPLAIN results.
     * @return array
     */
    private function analyze_explain( array $explain ): array {
        $warnings = [];

        foreach ( $explain as $row ) {
            // Check for full table scans
            if ( isset( $row['type'] ) && $row['type'] === 'ALL' && isset( $row['rows'] ) && (int) $row['rows'] > 10000 ) {
                $warnings[] = sprintf(
                    /* translators: 1: table name, 2: row count */
                    __( 'Full table scan on %1$s (%2$d rows) - query may be slow', 'querymind' ),
                    $row['table'] ?? 'unknown',
                    $row['rows']
                );
            }

            // Check for missing indexes
            if ( ( $row['key'] ?? null ) === null && ( $row['possible_keys'] ?? null ) !== null ) {
                $warnings[] = sprintf(
                    /* translators: %s: table name */
                    __( 'No index used on %s - consider adding index', 'querymind' ),
                    $row['table'] ?? 'unknown'
                );
            }

            // Check for filesort
            if ( isset( $row['Extra'] ) && strpos( $row['Extra'], 'Using filesort' ) !== false ) {
                $warnings[] = __( 'Using filesort - query may be slow for large datasets', 'querymind' );
            }

            // Check for temporary table
            if ( isset( $row['Extra'] ) && strpos( $row['Extra'], 'Using temporary' ) !== false ) {
                $warnings[] = __( 'Using temporary table - query may be memory intensive', 'querymind' );
            }
        }

        return $warnings;
    }

    /**
     * Get query validator.
     *
     * @return QueryValidator
     */
    public function get_validator(): QueryValidator {
        return $this->validator;
    }
}

/**
 * Execution result object.
 */
class ExecutionResult {

    /**
     * Whether execution was successful.
     *
     * @var bool
     */
    public bool $success;

    /**
     * Query results.
     *
     * @var array
     */
    public array $data;

    /**
     * Number of rows returned.
     *
     * @var int
     */
    public int $row_count;

    /**
     * Query execution time in seconds.
     *
     * @var float
     */
    public float $execution_time;

    /**
     * Column names in the result.
     *
     * @var array
     */
    public array $columns;

    /**
     * Execution errors.
     *
     * @var array
     */
    public array $errors;

    /**
     * Execution warnings.
     *
     * @var array
     */
    public array $warnings;

    /**
     * Constructor.
     *
     * @param bool   $success        Whether execution succeeded.
     * @param array  $data           Result data.
     * @param int    $row_count      Number of rows.
     * @param float  $execution_time Execution time.
     * @param array  $columns        Column names.
     * @param array  $errors         Errors.
     * @param array  $warnings       Warnings.
     */
    public function __construct(
        bool $success,
        array $data = [],
        int $row_count = 0,
        float $execution_time = 0,
        array $columns = [],
        array $errors = [],
        array $warnings = []
    ) {
        $this->success = $success;
        $this->data = $data;
        $this->row_count = $row_count;
        $this->execution_time = $execution_time;
        $this->columns = $columns;
        $this->errors = $errors;
        $this->warnings = $warnings;
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'success'        => $this->success,
            'data'           => $this->data,
            'row_count'      => $this->row_count,
            'execution_time' => $this->execution_time,
            'columns'        => $this->columns,
            'errors'         => $this->errors,
            'warnings'       => $this->warnings,
        ];
    }
}
