<?php
/**
 * Query Validator
 *
 * Validates SQL queries for safety before execution.
 *
 * @package QueryMind
 */

namespace QueryMind\Core;

/**
 * Query Validator class.
 *
 * Ensures only safe, read-only SQL queries are executed.
 */
class QueryValidator {

    /**
     * Forbidden SQL keywords that could modify data.
     */
    private const FORBIDDEN_KEYWORDS = [
        'INSERT',
        'UPDATE',
        'DELETE',
        'DROP',
        'ALTER',
        'TRUNCATE',
        'CREATE',
        'REPLACE',
        'RENAME',
        'GRANT',
        'REVOKE',
        'LOCK',
        'UNLOCK',
        'CALL',
        'EXECUTE',
        'EXEC',
        'INTO OUTFILE',
        'INTO DUMPFILE',
        'LOAD_FILE',
        'BENCHMARK',
        'SLEEP',
        'WAITFOR',
        'SET',
        'SHOW GRANTS',
    ];

    /**
     * Forbidden functions that could be dangerous.
     */
    private const FORBIDDEN_FUNCTIONS = [
        'LOAD_FILE',
        'INTO OUTFILE',
        'INTO DUMPFILE',
        'BENCHMARK',
        'SLEEP',
        'GET_LOCK',
        'RELEASE_LOCK',
        'IS_FREE_LOCK',
        'IS_USED_LOCK',
        'SYS_EXEC',
        'SYS_EVAL',
    ];

    /**
     * Maximum rows to return.
     */
    private int $max_rows;

    /**
     * Maximum query execution time (seconds).
     */
    private int $max_execution_time;

    /**
     * Schema Discovery instance.
     *
     * @var SchemaDiscovery
     */
    private SchemaDiscovery $schema_discovery;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->max_rows = absint( get_option( 'querymind_max_rows', 1000 ) );
        $this->max_execution_time = absint( get_option( 'querymind_query_timeout', 30 ) );
        $this->schema_discovery = new SchemaDiscovery();
    }

    /**
     * Validate and sanitize SQL query.
     *
     * @param string $sql SQL query to validate.
     * @return ValidationResult
     */
    public function validate( string $sql ): ValidationResult {
        $errors = [];
        $warnings = [];

        // Normalize SQL
        $normalized_sql = $this->normalize( $sql );

        // Check for forbidden keywords
        foreach ( self::FORBIDDEN_KEYWORDS as $keyword ) {
            $pattern = '/\b' . preg_quote( $keyword, '/' ) . '\b/i';
            if ( preg_match( $pattern, $normalized_sql ) ) {
                $errors[] = sprintf(
                    /* translators: %s: forbidden keyword */
                    __( 'Forbidden keyword detected: %s. Only SELECT queries are allowed.', 'querymind' ),
                    $keyword
                );
            }
        }

        // Check for forbidden functions
        foreach ( self::FORBIDDEN_FUNCTIONS as $func ) {
            if ( stripos( $normalized_sql, $func ) !== false ) {
                $errors[] = sprintf(
                    /* translators: %s: forbidden function */
                    __( 'Forbidden function detected: %s', 'querymind' ),
                    $func
                );
            }
        }

        // Must start with SELECT
        if ( ! preg_match( '/^\s*SELECT\b/i', $normalized_sql ) ) {
            $errors[] = __( 'Query must start with SELECT.', 'querymind' );
        }

        // Check for multiple statements (SQL injection attempt)
        if ( preg_match( '/;\s*\w/i', $normalized_sql ) ) {
            $errors[] = __( 'Multiple SQL statements are not allowed.', 'querymind' );
        }

        // Check for UNION injection attempts
        if ( preg_match( '/\bUNION\s+(ALL\s+)?SELECT\b/i', $normalized_sql ) ) {
            // UNION is allowed, but warn about potential injection
            $warnings[] = __( 'UNION SELECT detected. Ensure this is intentional.', 'querymind' );
        }

        // Check for comments (potential injection)
        if ( preg_match( '/(--|#|\/\*)/i', $normalized_sql ) ) {
            $warnings[] = __( 'SQL comments detected and will be removed.', 'querymind' );
            $normalized_sql = $this->remove_comments( $normalized_sql );
        }

        // Check for subqueries in dangerous positions
        if ( $this->has_dangerous_subquery( $normalized_sql ) ) {
            $errors[] = __( 'Potentially dangerous subquery detected.', 'querymind' );
        }

        // Ensure LIMIT clause exists
        if ( ! preg_match( '/\bLIMIT\s+\d+/i', $normalized_sql ) ) {
            $normalized_sql = $this->add_limit( $normalized_sql, $this->max_rows );
            $warnings[] = sprintf(
                /* translators: %d: max rows limit */
                __( 'LIMIT %d automatically added for safety.', 'querymind' ),
                $this->max_rows
            );
        }

        // Check existing LIMIT isn't too high
        if ( preg_match( '/\bLIMIT\s+(\d+)/i', $normalized_sql, $matches ) ) {
            if ( (int) $matches[1] > $this->max_rows ) {
                $normalized_sql = preg_replace(
                    '/\bLIMIT\s+\d+/i',
                    'LIMIT ' . $this->max_rows,
                    $normalized_sql
                );
                $warnings[] = sprintf(
                    /* translators: %d: max rows limit */
                    __( 'LIMIT reduced to maximum %d rows.', 'querymind' ),
                    $this->max_rows
                );
            }
        }

        // Validate table names exist
        $tables = $this->extract_tables( $normalized_sql );
        $invalid_tables = $this->check_tables_exist( $tables );
        if ( ! empty( $invalid_tables ) ) {
            $errors[] = sprintf(
                /* translators: %s: invalid table names */
                __( 'Invalid or unauthorized table(s): %s', 'querymind' ),
                implode( ', ', $invalid_tables )
            );
        }

        return new ValidationResult(
            valid: empty( $errors ),
            sql: $normalized_sql,
            errors: $errors,
            warnings: $warnings
        );
    }

    /**
     * Normalize SQL query.
     *
     * @param string $sql SQL query.
     * @return string
     */
    private function normalize( string $sql ): string {
        // Remove extra whitespace
        $sql = preg_replace( '/\s+/', ' ', trim( $sql ) );

        // Remove trailing semicolon
        $sql = rtrim( $sql, ';' );

        return $sql;
    }

    /**
     * Remove SQL comments.
     *
     * @param string $sql SQL query.
     * @return string
     */
    private function remove_comments( string $sql ): string {
        // Remove -- comments
        $sql = preg_replace( '/--.*$/m', '', $sql );

        // Remove # comments
        $sql = preg_replace( '/#.*$/m', '', $sql );

        // Remove /* */ comments
        $sql = preg_replace( '/\/\*.*?\*\//s', '', $sql );

        return trim( $sql );
    }

    /**
     * Add LIMIT clause to query.
     *
     * @param string $sql SQL query.
     * @param int    $limit Row limit.
     * @return string
     */
    private function add_limit( string $sql, int $limit ): string {
        // Check if already has LIMIT
        if ( preg_match( '/\bLIMIT\s+\d+/i', $sql ) ) {
            return $sql;
        }

        return $sql . ' LIMIT ' . $limit;
    }

    /**
     * Check for dangerous subqueries.
     *
     * @param string $sql SQL query.
     * @return bool
     */
    private function has_dangerous_subquery( string $sql ): bool {
        // Check for subqueries in WHERE that could cause DoS
        // This is a basic check - more sophisticated analysis could be added
        $subquery_count = substr_count( strtoupper( $sql ), 'SELECT' );
        return $subquery_count > 5; // Allow reasonable nested queries
    }

    /**
     * Extract table names from SQL.
     *
     * @param string $sql SQL query.
     * @return array
     */
    public function extract_tables( string $sql ): array {
        $tables = [];

        // Match FROM clause
        if ( preg_match_all( '/\bFROM\s+([`\w]+)/i', $sql, $matches ) ) {
            $tables = array_merge( $tables, $matches[1] );
        }

        // Match JOIN clauses
        if ( preg_match_all( '/\bJOIN\s+([`\w]+)/i', $sql, $matches ) ) {
            $tables = array_merge( $tables, $matches[1] );
        }

        return array_unique( array_map( function ( $t ) {
            return trim( $t, '`' );
        }, $tables ) );
    }

    /**
     * Check if tables exist in database.
     *
     * @param array $tables Table names.
     * @return array Invalid tables.
     */
    private function check_tables_exist( array $tables ): array {
        global $wpdb;

        $invalid = [];
        $allowed = $this->schema_discovery->get_allowed_tables();

        foreach ( $tables as $table ) {
            // Replace {prefix} placeholder
            $actual_table = str_replace( '{prefix}', $wpdb->prefix, $table );

            if ( ! in_array( $actual_table, $allowed, true ) ) {
                $invalid[] = $table;
            }
        }

        return $invalid;
    }

    /**
     * Get maximum rows setting.
     *
     * @return int
     */
    public function get_max_rows(): int {
        return $this->max_rows;
    }

    /**
     * Get maximum execution time setting.
     *
     * @return int
     */
    public function get_max_execution_time(): int {
        return $this->max_execution_time;
    }
}

/**
 * Validation result object.
 */
class ValidationResult {

    /**
     * Whether the validation passed.
     *
     * @var bool
     */
    public bool $valid;

    /**
     * The sanitized SQL query.
     *
     * @var string
     */
    public string $sql;

    /**
     * Validation errors.
     *
     * @var array
     */
    public array $errors;

    /**
     * Validation warnings.
     *
     * @var array
     */
    public array $warnings;

    /**
     * Constructor.
     *
     * @param bool   $valid    Whether validation passed.
     * @param string $sql      Sanitized SQL.
     * @param array  $errors   Errors found.
     * @param array  $warnings Warnings found.
     */
    public function __construct(
        bool $valid,
        string $sql,
        array $errors = [],
        array $warnings = []
    ) {
        $this->valid = $valid;
        $this->sql = $sql;
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
            'valid'    => $this->valid,
            'sql'      => $this->sql,
            'errors'   => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
