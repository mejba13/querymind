<?php
/**
 * Prompt Builder
 *
 * Builds AI prompts for SQL generation.
 *
 * @package QueryMind
 */

namespace QueryMind\AI;

/**
 * Prompt Builder class.
 *
 * Constructs optimized prompts for AI providers.
 */
class PromptBuilder {

    /**
     * Build a complete prompt for SQL generation.
     *
     * @param string $question User's question.
     * @param array  $schema   Database schema.
     * @param array  $options  Additional options.
     * @return string
     */
    public function build( string $question, array $schema, array $options = [] ): string {
        $parts = [];

        // Add context about the task
        $parts[] = $this->get_task_context();

        // Add schema information
        $parts[] = $this->format_schema( $schema );

        // Add integration-specific context
        $integrations = $options['integrations'] ?? [];
        if ( ! empty( $integrations ) ) {
            $parts[] = $this->get_integration_context( $integrations );
        }

        // Add the user question
        $parts[] = $this->format_question( $question );

        // Add output format instructions
        $parts[] = $this->get_output_format();

        return implode( "\n\n", array_filter( $parts ) );
    }

    /**
     * Get task context.
     *
     * @return string
     */
    private function get_task_context(): string {
        global $wpdb;

        return sprintf(
            "You are a SQL query generator for WordPress/MySQL databases.\n" .
            "Table prefix: %s\n" .
            "Current date: %s\n" .
            "Timezone: %s",
            $wpdb->prefix,
            current_time( 'Y-m-d' ),
            wp_timezone_string()
        );
    }

    /**
     * Format schema for prompt.
     *
     * @param array $schema Database schema.
     * @return string
     */
    private function format_schema( array $schema ): string {
        $lines = [ 'DATABASE SCHEMA:' ];

        foreach ( $schema as $table => $info ) {
            $description = $info['description'] ?? '';
            $row_count = $info['row_count'] ?? 0;
            $desc_text = $description ? " -- {$description}" : '';
            $count_text = $row_count > 0 ? " (~{$row_count} rows)" : '';

            $lines[] = "\n{$table}{$desc_text}{$count_text}";

            if ( isset( $info['columns'] ) && is_array( $info['columns'] ) ) {
                foreach ( $info['columns'] as $col ) {
                    $nullable = ( $col['nullable'] ?? false ) ? 'NULL' : 'NOT NULL';
                    $key = ! empty( $col['key'] ) ? " [{$col['key']}]" : '';
                    $lines[] = "  - {$col['name']} ({$col['type']}, {$nullable}){$key}";
                }
            }
        }

        return implode( "\n", $lines );
    }

    /**
     * Get integration-specific context.
     *
     * @param array $integrations Active integrations.
     * @return string
     */
    private function get_integration_context( array $integrations ): string {
        $context = [];

        if ( in_array( 'woocommerce', $integrations, true ) ) {
            $context[] = $this->get_woocommerce_context();
        }

        if ( in_array( 'learndash', $integrations, true ) ) {
            $context[] = $this->get_learndash_context();
        }

        if ( in_array( 'memberpress', $integrations, true ) ) {
            $context[] = $this->get_memberpress_context();
        }

        return implode( "\n\n", $context );
    }

    /**
     * Get WooCommerce context.
     *
     * @return string
     */
    private function get_woocommerce_context(): string {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $hpos_enabled = get_option( 'woocommerce_custom_orders_table_enabled' ) === 'yes';
        $hpos_note = $hpos_enabled
            ? "Orders are in {$prefix}wc_orders table (HPOS enabled)"
            : "Orders are in {$prefix}posts where post_type='shop_order'";

        $context = "WOOCOMMERCE CONTEXT:\n";
        $context .= $hpos_note . "\n\n";
        $context .= "ORDER STATUSES:\n";
        $context .= "- wc-pending: Pending payment\n";
        $context .= "- wc-processing: Processing (paid, not shipped)\n";
        $context .= "- wc-on-hold: On hold\n";
        $context .= "- wc-completed: Completed\n";
        $context .= "- wc-cancelled: Cancelled\n";
        $context .= "- wc-refunded: Refunded\n";
        $context .= "- wc-failed: Failed payment\n\n";
        $context .= "For revenue calculations, use status IN ('wc-completed', 'wc-processing')\n\n";
        $context .= "KEY RELATIONSHIPS:\n";
        $context .= "- {$prefix}wc_orders: Main order data (id, status, total_amount, customer_id, date_created_gmt)\n";
        $context .= "- {$prefix}woocommerce_order_items: Line items (order_item_id, order_id, order_item_name, order_item_type)\n";
        $context .= "- {$prefix}woocommerce_order_itemmeta: Item details (order_item_id, meta_key, meta_value)\n";
        $context .= "- {$prefix}wc_customer_lookup: Customer data (customer_id, user_id, email, first_name, last_name)";

        return $context;
    }

    /**
     * Get LearnDash context.
     *
     * @return string
     */
    private function get_learndash_context(): string {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $context = "LEARNDASH CONTEXT:\n";
        $context .= "Course content uses WordPress posts with custom post types:\n";
        $context .= "- sfwd-courses: Courses\n";
        $context .= "- sfwd-lessons: Lessons\n";
        $context .= "- sfwd-topic: Topics\n";
        $context .= "- sfwd-quiz: Quizzes\n\n";
        $context .= "Progress tracking in {$prefix}learndash_user_activity:\n";
        $context .= "- activity_type: 'course', 'lesson', 'topic', 'quiz'\n";
        $context .= "- activity_status: 0 (not started), 1 (in progress), 2 (completed)\n";
        $context .= "- activity_started, activity_completed: timestamps\n\n";
        $context .= "COMMON CALCULATIONS:\n";
        $context .= "- Completion rate: COUNT(activity_status=2) / COUNT(*) WHERE activity_type='course'\n";
        $context .= "- Average quiz score: From {$prefix}learndash_pro_quiz_statistic";

        return $context;
    }

    /**
     * Get MemberPress context.
     *
     * @return string
     */
    private function get_memberpress_context(): string {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $context = "MEMBERPRESS CONTEXT:\n";
        $context .= "Membership products are in posts where post_type='memberpressproduct'\n\n";
        $context .= "KEY TABLES:\n";
        $context .= "- {$prefix}mepr_transactions: Payment records (id, user_id, product_id, amount, status, created_at)\n";
        $context .= "- {$prefix}mepr_subscriptions: Recurring subscriptions (id, user_id, product_id, status, created_at)\n\n";
        $context .= "TRANSACTION STATUSES: pending, complete, refunded, failed\n";
        $context .= "SUBSCRIPTION STATUSES: active, suspended, cancelled, expired\n\n";
        $context .= "COMMON CALCULATIONS:\n";
        $context .= "- MRR: SUM(amount) from active subscriptions / billing periods\n";
        $context .= "- Churn rate: Cancelled in period / Active at start of period";

        return $context;
    }

    /**
     * Format the user question.
     *
     * @param string $question User's question.
     * @return string
     */
    private function format_question( string $question ): string {
        return "USER QUESTION: {$question}";
    }

    /**
     * Get output format instructions.
     *
     * @return string
     */
    private function get_output_format(): string {
        $format = "RULES:\n";
        $format .= "1. Generate ONLY SELECT queries (read-only)\n";
        $format .= "2. NEVER use DELETE, UPDATE, INSERT, DROP, ALTER, TRUNCATE\n";
        $format .= "3. Always add LIMIT clause (max 1000 rows unless aggregating)\n";
        $format .= "4. Use proper table prefixes as shown in schema\n";
        $format .= "5. Handle NULL values appropriately with COALESCE or IFNULL\n";
        $format .= "6. Use readable column aliases for clarity\n";
        $format .= "7. For date comparisons, use the database's current timezone\n";
        $format .= "8. For aggregations, use appropriate GROUP BY clauses\n\n";
        $format .= "OUTPUT FORMAT:\n";
        $format .= "Return ONLY a valid JSON object with these exact fields:\n";
        $format .= "{\n";
        $format .= '    "sql": "YOUR SQL QUERY HERE",' . "\n";
        $format .= '    "explanation": "Brief explanation of what this query does",' . "\n";
        $format .= '    "columns": ["list", "of", "result", "columns"],' . "\n";
        $format .= '    "chartType": "table|bar|line|pie|none"' . "\n";
        $format .= "}\n\n";
        $format .= "Choose chartType based on the data:\n";
        $format .= '- "table" for detailed records or lists' . "\n";
        $format .= '- "bar" for comparing categories' . "\n";
        $format .= '- "line" for time series data' . "\n";
        $format .= '- "pie" for showing proportions' . "\n";
        $format .= '- "none" for single values' . "\n\n";
        $format .= "Do not include any text outside the JSON object.";

        return $format;
    }
}
