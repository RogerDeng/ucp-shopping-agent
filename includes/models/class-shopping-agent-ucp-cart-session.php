<?php
/**
 * Cart Session Model
 *
 * @package Shopping_Agent_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class Shopping_Agent_UCP_Cart_Session
{

    /**
     * Table name
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'shopping_agent_shopping_agent_ucp_cart_sessions';
    }

    /**
     * Create a new cart session
     */
    public function create($api_key_id = null)
    {
        global $wpdb;

        $cart_id = wp_generate_uuid4();
        $expiry_hours = (int) get_option('shopping_agent_shopping_agent_ucp_cart_expiry_hours', 24);
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_hours} hours"));

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'id' => $cart_id,
                'api_key_id' => $api_key_id,
                'items' => wp_json_encode(array()),
                'status' => 'active',
                'expires_at' => $expires_at,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error(
                'shopping_agent_shopping_agent_ucp_cart_creation_failed',
                __('Failed to create cart.', 'shopping-agent-with-ucp'),
                array('status' => 500)
            );
        }

        return $this->get($cart_id);
    }

    /**
     * Get cart by ID
     */
    public function get($cart_id)
    {
        global $wpdb;

        $cart = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %s",
                $cart_id
            )
        );

        if (!$cart) {
            return null;
        }

        // Parse JSON fields
        $cart->items = json_decode($cart->items, true) ?: array();
        $cart->shipping_address = $cart->shipping_address ? json_decode($cart->shipping_address, true) : null;
        $cart->billing_address = $cart->billing_address ? json_decode($cart->billing_address, true) : null;
        $cart->coupon_codes = $cart->coupon_codes ? json_decode($cart->coupon_codes, true) : array();

        return $cart;
    }

    /**
     * Update cart items
     */
    public function update_items($cart_id, $items)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            array(
                'items' => wp_json_encode($items),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $cart_id),
            array('%s', '%s'),
            array('%s')
        );
    }

    /**
     * Update cart addresses
     */
    public function update_addresses($cart_id, $shipping_address = null, $billing_address = null)
    {
        global $wpdb;

        $data = array('updated_at' => current_time('mysql'));
        $formats = array('%s');

        if ($shipping_address !== null) {
            $data['shipping_address'] = wp_json_encode($shipping_address);
            $formats[] = '%s';
        }

        if ($billing_address !== null) {
            $data['billing_address'] = wp_json_encode($billing_address);
            $formats[] = '%s';
        }

        return $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $cart_id),
            $formats,
            array('%s')
        );
    }

    /**
     * Update cart coupons
     */
    public function update_coupons($cart_id, $coupon_codes)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            array(
                'coupon_codes' => wp_json_encode($coupon_codes),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $cart_id),
            array('%s', '%s'),
            array('%s')
        );
    }

    /**
     * Update cart status
     */
    public function update_status($cart_id, $status)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            array(
                'status' => $status,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $cart_id),
            array('%s', '%s'),
            array('%s')
        );
    }

    /**
     * Delete cart
     */
    public function delete($cart_id)
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $cart_id),
            array('%s')
        );

        return $result !== false && $result > 0;
    }

    /**
     * Clean up expired carts
     */
    public function cleanup_expired()
    {
        global $wpdb;

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE expires_at < %s AND status = 'active'",
                current_time('mysql')
            )
        );
    }

    /**
     * Calculate cart totals
     */
    public function calculate_totals($cart)
    {
        $subtotal = 0;
        $items_count = 0;

        foreach ($cart->items as $item) {
            $subtotal += $item['line_total'] ?? 0;
            $items_count += $item['quantity'] ?? 0;
        }

        // Get currency decimals
        $decimals = wc_get_price_decimals();

        return array(
            'subtotal' => $subtotal,
            'discount' => 0, // Will be calculated when coupons are applied
            'shipping' => 0, // Will be calculated when shipping is selected
            'tax' => 0, // Will be calculated based on address
            'total' => $subtotal,
            'items_count' => $items_count,
            'currency' => get_woocommerce_currency(),
        );
    }
}
