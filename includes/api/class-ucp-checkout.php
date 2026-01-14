<?php
/**
 * Checkout Endpoint
 *
 * @package WC_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_UCP_Checkout extends WC_UCP_REST_Controller
{

    protected $rest_base = 'checkout';

    /**
     * Checkout sessions table name
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ucp_checkout_sessions';
    }

    /**
     * Register routes
     */
    public function register_routes()
    {
        // Create checkout session
        register_rest_route($this->namespace, '/' . $this->rest_base . '/sessions', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_session'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'items' => array(
                    'type' => 'array',
                    'required' => true,
                ),
                'shipping_address' => array(
                    'type' => 'object',
                    'required' => true,
                ),
                'billing_address' => array(
                    'type' => 'object',
                ),
            ),
        ));

        // Get checkout session
        register_rest_route($this->namespace, '/' . $this->rest_base . '/sessions/(?P<id>[a-f0-9\-]+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_session'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'id' => array(
                    'type' => 'string',
                    'required' => true,
                ),
            ),
        ));

        // Update checkout session
        register_rest_route($this->namespace, '/' . $this->rest_base . '/sessions/(?P<id>[a-f0-9\-]+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array($this, 'update_session'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'id' => array(
                    'type' => 'string',
                    'required' => true,
                ),
                'shipping_method' => array(
                    'type' => 'string',
                ),
                'payment_method' => array(
                    'type' => 'string',
                ),
                'shipping_address' => array(
                    'type' => 'object',
                ),
                'billing_address' => array(
                    'type' => 'object',
                ),
                'coupon_codes' => array(
                    'type' => 'array',
                ),
            ),
        ));

        // Confirm checkout
        register_rest_route($this->namespace, '/' . $this->rest_base . '/sessions/(?P<id>[a-f0-9\-]+)/confirm', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'confirm_checkout'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'id' => array(
                    'type' => 'string',
                    'required' => true,
                ),
                'payment_method' => array(
                    'type' => 'string',
                ),
            ),
        ));
    }

    /**
     * Create checkout session from cart
     */
    public function create_session_from_cart($cart, $shipping_address, $billing_address)
    {
        global $wpdb;

        $session_id = wp_generate_uuid4();
        $expiry_minutes = (int) get_option('wc_ucp_checkout_expiry', 30);
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_minutes} minutes"));

        $api_key = WC_UCP_Auth::get_current_api_key();

        // Calculate totals
        $totals = $this->calculate_totals($cart->items, $shipping_address);

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'id' => $session_id,
                'cart_id' => $cart->id,
                'api_key_id' => $api_key ? $api_key->id : null,
                'items' => wp_json_encode($cart->items),
                'shipping_address' => wp_json_encode($shipping_address),
                'billing_address' => wp_json_encode($billing_address),
                'totals' => wp_json_encode($totals),
                'status' => 'pending',
                'expires_at' => $expires_at,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return $this->error_response(
                'checkout_creation_failed',
                __('Failed to create checkout session.', 'ucp-shopping-agent'),
                500
            );
        }

        return $this->format_session($this->get_session_data($session_id));
    }

    /**
     * Create checkout session directly
     */
    public function create_session($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        global $wpdb;

        $items = $request->get_param('items');
        $shipping_address = $request->get_param('shipping_address');
        $billing_address = $request->get_param('billing_address') ?: $shipping_address;

        // Validate and format items
        $formatted_items = array();
        foreach ($items as $item) {
            $product_id = $item['product_id'] ?? null;
            $sku = $item['sku'] ?? null;
            $quantity = (int) ($item['quantity'] ?? 1);

            if (!$product_id && $sku) {
                $product_id = wc_get_product_id_by_sku($sku);
            }

            if (!$product_id) {
                continue;
            }

            $variation_id = $item['variation_id'] ?? null;
            $product = wc_get_product($variation_id ?: $product_id);

            if (!$product || $product->get_status() !== 'publish') {
                continue;
            }

            $item_key = md5($product_id . '_' . $variation_id . '_' . time());
            $formatted_items[$item_key] = array(
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'quantity' => $quantity,
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'price' => $this->format_price($product->get_price()),
                'line_total' => $this->format_price($product->get_price()) * $quantity,
            );
        }

        if (empty($formatted_items)) {
            return $this->error_response(
                'no_valid_items',
                __('No valid items provided.', 'ucp-shopping-agent'),
                400
            );
        }

        $session_id = wp_generate_uuid4();
        $expiry_minutes = (int) get_option('wc_ucp_checkout_expiry', 30);
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_minutes} minutes"));

        $api_key = WC_UCP_Auth::get_current_api_key();
        $totals = $this->calculate_totals($formatted_items, $shipping_address);

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'id' => $session_id,
                'api_key_id' => $api_key ? $api_key->id : null,
                'items' => wp_json_encode($formatted_items),
                'shipping_address' => wp_json_encode($shipping_address),
                'billing_address' => wp_json_encode($billing_address),
                'totals' => wp_json_encode($totals),
                'status' => 'pending',
                'expires_at' => $expires_at,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return $this->error_response(
                'checkout_creation_failed',
                __('Failed to create checkout session.', 'ucp-shopping-agent'),
                500
            );
        }

        return $this->success_response($this->format_session($this->get_session_data($session_id)));
    }

    /**
     * Get checkout session
     */
    public function get_session($request)
    {
        $session_id = $request->get_param('id');
        $session = $this->get_session_or_error($session_id);

        if (is_wp_error($session)) {
            return $session;
        }

        return $this->success_response($this->format_session($session));
    }

    /**
     * Update checkout session
     */
    public function update_session($request)
    {
        global $wpdb;

        $session_id = $request->get_param('id');
        $session = $this->get_session_or_error($session_id);

        if (is_wp_error($session)) {
            return $session;
        }

        $update_data = array('updated_at' => current_time('mysql'));
        $update_format = array('%s');

        // Update shipping method
        if ($shipping_method = $request->get_param('shipping_method')) {
            $update_data['shipping_method'] = sanitize_text_field($shipping_method);
            $update_format[] = '%s';
        }

        // Update payment method
        if ($payment_method = $request->get_param('payment_method')) {
            $update_data['payment_method'] = sanitize_text_field($payment_method);
            $update_format[] = '%s';
        }

        // Update shipping address
        if ($shipping_address = $request->get_param('shipping_address')) {
            $update_data['shipping_address'] = wp_json_encode($shipping_address);
            $update_format[] = '%s';
        }

        // Update billing address
        if ($billing_address = $request->get_param('billing_address')) {
            $update_data['billing_address'] = wp_json_encode($billing_address);
            $update_format[] = '%s';
        }

        // Update coupons
        if ($coupon_codes = $request->get_param('coupon_codes')) {
            $update_data['coupon_codes'] = wp_json_encode($coupon_codes);
            $update_format[] = '%s';
        }

        $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $session_id),
            $update_format,
            array('%s')
        );

        // Recalculate totals
        $session = $this->get_session_data($session_id);
        $shipping_address = $session->shipping_address ? json_decode($session->shipping_address, true) : array();
        $items = $session->items ? json_decode($session->items, true) : array();
        $totals = $this->calculate_totals($items, $shipping_address, $session->shipping_method);

        $wpdb->update(
            $this->table_name,
            array('totals' => wp_json_encode($totals)),
            array('id' => $session_id),
            array('%s'),
            array('%s')
        );

        // Check if session is ready
        if (!empty($session->shipping_method) || !empty($update_data['shipping_method'])) {
            $wpdb->update(
                $this->table_name,
                array('status' => 'ready'),
                array('id' => $session_id),
                array('%s'),
                array('%s')
            );
        }

        return $this->success_response($this->format_session($this->get_session_data($session_id)));
    }

    /**
     * Confirm checkout and create order
     */
    public function confirm_checkout($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        global $wpdb;

        $session_id = $request->get_param('id');
        $session = $this->get_session_or_error($session_id);

        if (is_wp_error($session)) {
            return $session;
        }

        // Validate session is ready
        if ($session->status === 'confirmed') {
            return $this->error_response(
                'already_confirmed',
                __('This checkout session has already been confirmed.', 'ucp-shopping-agent'),
                400
            );
        }

        $items = json_decode($session->items, true);
        $shipping_address = json_decode($session->shipping_address, true);
        $billing_address = $session->billing_address ? json_decode($session->billing_address, true) : $shipping_address;

        // Create WooCommerce order
        $order = wc_create_order();

        if (is_wp_error($order)) {
            return $this->error_response(
                'order_creation_failed',
                __('Failed to create order.', 'ucp-shopping-agent'),
                500
            );
        }

        // Add items to order
        foreach ($items as $item) {
            $product = wc_get_product($item['variation_id'] ?: $item['product_id']);
            if ($product) {
                $order->add_product($product, $item['quantity']);
            }
        }

        // Set addresses
        $order->set_address($this->format_address_for_order($shipping_address), 'shipping');
        $order->set_address($this->format_address_for_order($billing_address), 'billing');

        // Set payment method
        $payment_method = $request->get_param('payment_method') ?: $session->payment_method;
        if ($payment_method) {
            $order->set_payment_method($payment_method);
        }

        // Add meta for UCP tracking
        $order->add_meta_data('_ucp_checkout_session_id', $session_id);
        $order->add_meta_data('_ucp_created', true);

        // Calculate totals and save
        $order->calculate_totals();
        $order->save();

        // Update session
        $wpdb->update(
            $this->table_name,
            array(
                'status' => 'confirmed',
                'order_id' => $order->get_id(),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $session_id),
            array('%s', '%d', '%s'),
            array('%s')
        );

        // Trigger webhook
        do_action('wc_ucp_order_created', $order);

        return $this->success_response(array(
            'checkout_session_id' => $session_id,
            'order' => array(
                'id' => $order->get_id(),
                'number' => $order->get_order_number(),
                'status' => $order->get_status(),
                'total' => array(
                    'amount' => $this->format_price($order->get_total()),
                    'currency' => $order->get_currency(),
                ),
                'payment_url' => $order->get_checkout_payment_url(),
                'created_at' => $order->get_date_created() ? $order->get_date_created()->format('c') : null,
            ),
        ));
    }

    /**
     * Get session data from database
     */
    private function get_session_data($session_id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %s",
                $session_id
            )
        );
    }

    /**
     * Get session or return error
     */
    private function get_session_or_error($session_id)
    {
        $session = $this->get_session_data($session_id);

        if (!$session) {
            return $this->error_response(
                'session_not_found',
                __('Checkout session not found.', 'ucp-shopping-agent'),
                404
            );
        }

        // Check if expired
        if (strtotime($session->expires_at) < time() && $session->status !== 'confirmed') {
            return $this->error_response(
                'session_expired',
                __('Checkout session has expired.', 'ucp-shopping-agent'),
                410
            );
        }

        return $session;
    }

    /**
     * Calculate totals
     */
    private function calculate_totals($items, $shipping_address, $shipping_method = null)
    {
        $subtotal = 0;
        $items_count = 0;

        foreach ($items as $item) {
            $subtotal += $item['line_total'] ?? 0;
            $items_count += $item['quantity'] ?? 0;
        }

        // TODO: Calculate shipping based on method and address
        $shipping = 0;

        // TODO: Calculate tax based on address
        $tax = 0;

        $total = $subtotal + $shipping + $tax;

        return array(
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax' => $tax,
            'discount' => 0,
            'total' => $total,
            'items_count' => $items_count,
            'currency' => get_woocommerce_currency(),
        );
    }

    /**
     * Format session for response
     */
    private function format_session($session)
    {
        $items = $session->items ? json_decode($session->items, true) : array();
        $formatted_items = array();
        foreach ($items as $key => $item) {
            $formatted_items[] = array_merge(array('key' => $key), $item);
        }

        return array(
            'id' => $session->id,
            'cart_id' => $session->cart_id,
            'status' => $session->status,
            'items' => $formatted_items,
            'shipping_address' => $session->shipping_address ? json_decode($session->shipping_address, true) : null,
            'billing_address' => $session->billing_address ? json_decode($session->billing_address, true) : null,
            'shipping_method' => $session->shipping_method,
            'payment_method' => $session->payment_method,
            'totals' => $session->totals ? json_decode($session->totals, true) : null,
            'order_id' => $session->order_id ? (int) $session->order_id : null,
            'expires_at' => $session->expires_at,
            'created_at' => $session->created_at,
            'updated_at' => $session->updated_at,
        );
    }

    /**
     * Format address for WooCommerce order
     */
    private function format_address_for_order($address)
    {
        return array(
            'first_name' => $address['first_name'] ?? '',
            'last_name' => $address['last_name'] ?? '',
            'company' => $address['company'] ?? '',
            'address_1' => $address['address_1'] ?? '',
            'address_2' => $address['address_2'] ?? '',
            'city' => $address['city'] ?? '',
            'state' => $address['state'] ?? '',
            'postcode' => $address['postcode'] ?? '',
            'country' => $address['country'] ?? '',
            'email' => $address['email'] ?? '',
            'phone' => $address['phone'] ?? '',
        );
    }
}
