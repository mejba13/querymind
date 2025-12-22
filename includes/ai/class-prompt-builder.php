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

        return <<<CONTEXT
WOOCOMMERCE CONTEXT:
{$hpos_note}

ORDER STATUSES:
- wc-pending: Pending payment
- wc-processing: Processing (paid, not shipped)
- wc-on-hold: On hold
- wc-completed: Completed
- wc-cancelled: Cancelled
- wc-refunded: Refunded
- wc-failed: Failed payment

For revenue calculations, use status IN ('wc-completed', 'wc-processing')

KEY RELATIONSHIPS:
- {$prefix}wc_orders: Main order data (id, status, total_amount, customer_id, date_created_gmt)
- {$prefix}woocommerce_order_items: Line items (order_item_id, order_id, order_item_name, order_item_type)
- {$prefix}woocommerce_order_itemmeta: Item details (order_item_id, meta_key, meta_value)
- {$prefix}wc_customer_lookup: Customer data (customer_id, user_id, email, first_name, last_name)
CONTEXT;
    }

    /**
     * Get LearnDash context.
     *
     * @return string
     */
    private function get_learndash_context(): string {
        global $wpdb;
        $prefix = $wpdb->prefix;

        return <<<CONTEXT
LEARNDASH CONTEXT:
Course content uses WordPress posts with custom post types:
- sfwd-courses: Courses
- sfwd-lessons: Lessons
- sfwd-topic: Topics
- sfwd-quiz: Quizzes

Progress tracking in {$prefix}learndash_user_activity:
- activity_type: 'course', 'lesson', 'topic', 'quiz'
- activity_status: 0 (not started), 1 (in progress), 2 (completed)
- activity_started, activity_completed: timestamps

COMMON CALCULATIONS:
- Completion rate: COUNT(activity_status=2) / COUNT(*) WHERE activity_type='course'
- Average quiz score: From {$prefix}learndash_pro_quiz_statistic
CONTEXT;
    }

    /**
     * Get MemberPress context.
     *
     * @return string
     */
    private function get_memberpress_context(): string {
        global $wpdb;
        $prefix = $wpdb->prefix;

        return <<<CONTEXT
MEMBERPRESS CONTEXT:
Membership products are in posts where post_type='memberpressproduct'

KEY TABLES:
- {$prefix}mepr_transactions: Payment records (id, user_id, product_id, amount, status, created_at)
- {$prefix}mepr_subscriptions: Recurring subscriptions (id, user_id, product_id, status, created_at)

TRANSACTION STATUSES: pending, complete, refunded, failed
SUBSCRIPTION STATUSES: active, suspended, cancelled, expired

COMMON CALCULATIONS:
- MRR: SUM(amount) from active subscriptions / billing periods
- Churn rate: Cancelled in period / Active at start of period
CONTEXT;
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
        return <<<FORMAT
RULES:
1. Generate ONLY SELECT queries (read-only)
2. NEVER use DELETE, UPDATE, INSERT, DROP, ALTER, TRUNCATE
3. Always add LIMIT clause (max 1000 rows unless aggregating)
4. Use proper table prefixes as shown in schema
5. Handle NULL values appropriately with COALESCE or IFNULL
6. Use readable column aliases for clarity
7. For date comparisons, use the database's current timezone
8. For aggregations, use appropriate GROUP BY clauses

OUTPUT FORMAT:
Return ONLY a valid JSON object with these exact fields:
{
    "sql": "YOUR SQL QUERY HERE",
    "explanation": "Brief explanation of what this query does",
    "columns": ["list", "of", "result", "columns"],
    "chartType": "table|bar|line|pie|none"
}

Choose chartType based on the data:
- "table" for detailed records or lists
- "bar" for comparing categories
- "line" for time series data
- "pie" for showing proportions
- "none" for single values

Do not include any text outside the JSON object.
FORMAT;
    }
}
