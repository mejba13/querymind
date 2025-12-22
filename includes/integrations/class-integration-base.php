<?php
/**
 * Integration Base Class
 *
 * @package QueryMind
 */

namespace QueryMind\Integrations;

/**
 * Abstract base class for plugin integrations.
 */
abstract class IntegrationBase {

    /**
     * Get integration name.
     *
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * Get integration slug.
     *
     * @return string
     */
    abstract public function get_slug(): string;

    /**
     * Check if integration is active.
     *
     * @return bool
     */
    abstract public function is_active(): bool;

    /**
     * Get tables for this integration.
     *
     * @return array
     */
    abstract public function get_tables(): array;

    /**
     * Get AI prompt context for this integration.
     *
     * @return string
     */
    abstract public function get_prompt_context(): string;

    /**
     * Get sample questions for this integration.
     *
     * @return array
     */
    abstract public function get_sample_questions(): array;

    /**
     * Get schema for this integration.
     *
     * @return array
     */
    public function get_schema(): array {
        if ( ! $this->is_active() ) {
            return [];
        }

        $schema = [];
        foreach ( $this->get_tables() as $table ) {
            $schema[ $table ] = $this->describe_table( $table );
        }

        return $schema;
    }

    /**
     * Describe a table.
     *
     * @param string $table Table name.
     * @return array
     */
    protected function describe_table( string $table ): array {
        global $wpdb;

        $columns = $wpdb->get_results(
            $wpdb->prepare(
                'SHOW FULL COLUMNS FROM `%1s`',
                $table
            ),
            ARRAY_A
        );

        return array_map( function ( $col ) {
            return [
                'name'     => $col['Field'],
                'type'     => $col['Type'],
                'nullable' => $col['Null'] === 'YES',
                'key'      => $col['Key'],
            ];
        }, $columns ?: [] );
    }

    /**
     * Check if table exists.
     *
     * @param string $table Table name.
     * @return bool
     */
    protected function table_exists( string $table ): bool {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
        ) === $table;
    }
}
