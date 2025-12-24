<?php
/**
 * WooCommerce Integration
 *
 * @package QueryMind
 */

namespace QueryMind\Integrations;

/**
 * WooCommerce integration class.
 */
class WooCommerce extends IntegrationBase {

    /**
     * Get integration name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'WooCommerce';
    }

    /**
     * Get integration slug.
     *
     * @return string
     */
    public function get_slug(): string {
        return 'woocommerce';
    }

    /**
     * Check if integration is active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Get tables for this integration.
     *
     * @return array
     */
    public function get_tables(): array {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'wc_orders',
            $wpdb->prefix . 'wc_orders_meta',
            $wpdb->prefix . 'wc_order_stats',
            $wpdb->prefix . 'wc_order_product_lookup',
            $wpdb->prefix . 'wc_order_coupon_lookup',
            $wpdb->prefix . 'wc_customer_lookup',
            $wpdb->prefix . 'woocommerce_order_items',
            $wpdb->prefix . 'woocommerce_order_itemmeta',
            $wpdb->prefix . 'wc_product_meta_lookup',
        ];

        // Legacy tables (pre-HPOS)
        if ( ! $this->is_hpos_enabled() ) {
            $tables[] = $wpdb->posts;
            $tables[] = $wpdb->postmeta;
        }

        return array_filter( $tables, [ $this, 'table_exists' ] );
    }

    /**
     * Get AI prompt context for this integration.
     *
     * @return string
     */
    public function get_prompt_context(): string {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $hpos_note = $this->is_hpos_enabled()
            ? "Orders are stored in {$prefix}wc_orders table (HPOS enabled)"
            : "Orders are stored in {$prefix}posts table where post_type='shop_order'";

        $context = "WOOCOMMERCE INTEGRATION:\n";
        $context .= $hpos_note . "\n\n";
        $context .= "KEY TABLES:\n";
        $context .= "- {$prefix}wc_orders: Order records (id, status, total_amount, customer_id, date_created_gmt)\n";
        $context .= "- {$prefix}wc_order_stats: Order statistics (order_id, total_sales, tax_total, shipping_total, net_total)\n";
        $context .= "- {$prefix}wc_customer_lookup: Customer data (customer_id, user_id, email, first_name, last_name, city, country)\n";
        $context .= "- {$prefix}woocommerce_order_items: Line items (order_item_id, order_id, order_item_name, order_item_type)\n";
        $context .= "- {$prefix}woocommerce_order_itemmeta: Item meta (_qty, _line_total, _product_id, _variation_id)\n";
        $context .= "- {$prefix}wc_product_meta_lookup: Product data for queries (_price, _regular_price, _sale_price, _stock)\n\n";
        $context .= "ORDER STATUSES (always prefixed with 'wc-'):\n";
        $context .= "- wc-pending: Pending payment\n";
        $context .= "- wc-processing: Processing (paid, awaiting fulfillment)\n";
        $context .= "- wc-on-hold: On hold\n";
        $context .= "- wc-completed: Completed\n";
        $context .= "- wc-cancelled: Cancelled\n";
        $context .= "- wc-refunded: Refunded\n";
        $context .= "- wc-failed: Failed payment\n\n";
        $context .= "IMPORTANT NOTES:\n";
        $context .= "- For revenue calculations, include status IN ('wc-completed', 'wc-processing')\n";
        $context .= "- Order total is in 'total_amount' column in wc_orders or 'total_sales' in wc_order_stats\n";
        $context .= "- Customer data may be in wc_customer_lookup or linked via customer_id to users table\n";
        $context .= "- Product data is in posts table (post_type='product') with meta in postmeta\n";
        $context .= "- Line item quantities and totals are in woocommerce_order_itemmeta with meta_keys: _qty, _line_total";

        return $context;
    }

    /**
     * Get sample questions for this integration.
     *
     * @return array
     */
    public function get_sample_questions(): array {
        return [
            __( 'What was our total revenue this month?', 'querymind' ),
            __( 'Show me the top 10 customers by total spend', 'querymind' ),
            __( 'How many orders are pending?', 'querymind' ),
            __( 'What is our average order value?', 'querymind' ),
            __( 'Which products sold the most last week?', 'querymind' ),
            __( 'Show me refunded orders from this month', 'querymind' ),
            __( 'What is the revenue breakdown by country?', 'querymind' ),
            __( 'How many new customers did we get today?', 'querymind' ),
            __( 'What are the top selling product categories?', 'querymind' ),
            __( 'Show me orders with failed payments', 'querymind' ),
        ];
    }

    /**
     * Check if WooCommerce HPOS is enabled.
     *
     * @return bool
     */
    private function is_hpos_enabled(): bool {
        return get_option( 'woocommerce_custom_orders_table_enabled' ) === 'yes';
    }

    /**
     * Get common WooCommerce metrics.
     *
     * @return array
     */
    public function get_common_metrics(): array {
        return [
            'revenue'             => 'SUM(total_amount) for completed/processing orders',
            'aov'                 => 'Average Order Value = Total Revenue / Order Count',
            'orders_count'        => 'COUNT of orders by status',
            'customer_ltv'        => 'Lifetime Value = Total spent by customer',
            'conversion_rate'     => 'Requires external analytics data',
            'refund_rate'         => 'Refunded orders / Total orders',
            'repeat_customer_rate' => 'Customers with >1 order / Total customers',
        ];
    }
}
