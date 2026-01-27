<?php
/**
 * Cart Endpoint
 *
 * @package Shopping_Agent_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class Shopping_Agent_UCP_Cart extends Shopping_Agent_UCP_REST_Controller
{

    protected $rest_base = 'carts';

    /**
     * Register routes
     */
    public function register_routes()
    {
        // Create cart
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_cart'),
            'permission_callback' => array($this, 'write_permissions_check'),
        ));

        // Get cart
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[a-f0-9\-]+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_cart'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'id' => array(
                    'type' => 'string',
                    'required' => true,
                    'pattern' => '[a-f0-9\-]+',
                ),
            ),
        ));

        // Delete cart
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[a-f0-9\-]+)', array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => array($this, 'delete_cart'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'id' => array(
                    'type' => 'string',
                    'required' => true,
                ),
            ),
        ));

        // Add item to cart
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[a-f0-9\-]+)/items', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'add_item'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'id' => array(
                    'type' => 'string',
                    'required' => true,
                ),
                'product_id' => array(
                    'type' => 'integer',
                    'description' => __('Product ID', 'shopping-agent-with-ucp'),
                ),
                'sku' => array(
                    'type' => 'string',
                    'description' => __('Product SKU', 'shopping-agent-with-ucp'),
                ),
                'variation_id' => array(
                    'type' => 'integer',
                    'description' => __('Variation ID for variable products', 'shopping-agent-with-ucp'),
                ),
                'quantity' => array(
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                ),
            ),
        ));

        // Update item quantity
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[a-f0-9\-]+)/items/(?P<item_key>[a-f0-9]+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array($this, 'update_item'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'id' => array(
                    'type' => 'string',
                    'required' => true,
                ),
                'item_key' => array(
                    'type' => 'string',
                    'required' => true,
                ),
                'quantity' => array(
                    'type' => 'integer',
                    'required' => true,
                    'minimum' => 0,
                ),
            ),
        ));

        // Remove item
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[a-f0-9\-]+)/items/(?P<item_key>[a-f0-9]+)', array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => array($this, 'remove_item'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'id' => array(
                    'type' => 'string',
                    'required' => true,
                ),
                'item_key' => array(
                    'type' => 'string',
                    'required' => true,
                ),
            ),
        ));

        // Convert to checkout
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[a-f0-9\-]+)/checkout', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'convert_to_checkout'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'id' => array(
                    'type' => 'string',
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
    }

    /**
     * Create a new cart
     */
    public function create_cart($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $api_key = Shopping_Agent_UCP_Auth::get_current_api_key();
        $api_key_id = $api_key ? $api_key->id : null;

        $cart_model = new Shopping_Agent_UCP_Cart_Session();
        $cart = $cart_model->create($api_key_id);

        if (is_wp_error($cart)) {
            return $cart;
        }

        return $this->success_response($this->format_cart($cart));
    }

    /**
     * Get cart
     */
    public function get_cart($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $cart_id = $request->get_param('id');
        $cart = $this->get_cart_or_error($cart_id);

        if (is_wp_error($cart)) {
            return $cart;
        }

        return $this->success_response($this->format_cart($cart));
    }

    /**
     * Delete cart
     */
    public function delete_cart($request)
    {
        $cart_id = $request->get_param('id');
        $cart = $this->get_cart_or_error($cart_id);

        if (is_wp_error($cart)) {
            return $cart;
        }

        $cart_model = new Shopping_Agent_UCP_Cart_Session();
        $cart_model->delete($cart_id);

        return $this->success_response(array(
            'deleted' => true,
            'id' => $cart_id,
        ));
    }

    /**
     * Add item to cart
     */
    public function add_item($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $cart_id = $request->get_param('id');
        $cart = $this->get_cart_or_error($cart_id);

        if (is_wp_error($cart)) {
            return $cart;
        }

        // Get product by ID or SKU
        $product_id = $request->get_param('product_id');
        $sku = $request->get_param('sku');
        $variation_id = $request->get_param('variation_id');
        $quantity = (int) $request->get_param('quantity') ?: 1;

        if (!$product_id && $sku) {
            $product_id = wc_get_product_id_by_sku($sku);
        }

        if (!$product_id) {
            return $this->error_response(
                'invalid_product',
                __('Product ID or SKU is required.', 'shopping-agent-with-ucp'),
                400
            );
        }

        $product = wc_get_product($variation_id ?: $product_id);

        if (!$product || $product->get_status() !== 'publish') {
            return $this->error_response(
                'product_not_found',
                __('Product not found.', 'shopping-agent-with-ucp'),
                404
            );
        }

        // Check stock
        if (!$product->is_in_stock()) {
            return $this->error_response(
                'out_of_stock',
                __('Product is out of stock.', 'shopping-agent-with-ucp'),
                400
            );
        }

        if ($product->managing_stock() && $product->get_stock_quantity() < $quantity) {
            return $this->error_response(
                'insufficient_stock',
                sprintf(
                    /* translators: %d: available quantity */
                    __('Only %d items available.', 'shopping-agent-with-ucp'),
                    $product->get_stock_quantity()
                ),
                400
            );
        }

        // Generate item key
        $item_key = md5($product_id . '_' . $variation_id . '_' . time());

        // Add to cart items
        $items = $cart->items;
        $items[$item_key] = array(
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'quantity' => $quantity,
            'name' => $product->get_name(),
            'sku' => $product->get_sku(),
            'price' => $this->format_price($product->get_price()),
            'line_total' => $this->format_price($product->get_price()) * $quantity,
            'image' => $product->get_image_id() ? wp_get_attachment_url($product->get_image_id()) : null,
        );

        $cart_model = new Shopping_Agent_UCP_Cart_Session();
        $cart_model->update_items($cart_id, $items);

        $cart = $cart_model->get($cart_id);

        return $this->success_response($this->format_cart($cart));
    }

    /**
     * Update item quantity
     */
    public function update_item($request)
    {
        $cart_id = $request->get_param('id');
        $item_key = $request->get_param('item_key');
        $quantity = (int) $request->get_param('quantity');

        $cart = $this->get_cart_or_error($cart_id);

        if (is_wp_error($cart)) {
            return $cart;
        }

        $items = $cart->items;

        if (!isset($items[$item_key])) {
            return $this->error_response(
                'item_not_found',
                __('Cart item not found.', 'shopping-agent-with-ucp'),
                404
            );
        }

        if ($quantity === 0) {
            // Remove item
            unset($items[$item_key]);
        } else {
            // Update quantity and recalculate line total
            $price = $items[$item_key]['price'];
            $items[$item_key]['quantity'] = $quantity;
            $items[$item_key]['line_total'] = $price * $quantity;
        }

        $cart_model = new Shopping_Agent_UCP_Cart_Session();
        $cart_model->update_items($cart_id, $items);

        $cart = $cart_model->get($cart_id);

        return $this->success_response($this->format_cart($cart));
    }

    /**
     * Remove item from cart
     */
    public function remove_item($request)
    {
        $cart_id = $request->get_param('id');
        $item_key = $request->get_param('item_key');

        $cart = $this->get_cart_or_error($cart_id);

        if (is_wp_error($cart)) {
            return $cart;
        }

        $items = $cart->items;

        if (!isset($items[$item_key])) {
            return $this->error_response(
                'item_not_found',
                __('Cart item not found.', 'shopping-agent-with-ucp'),
                404
            );
        }

        unset($items[$item_key]);

        $cart_model = new Shopping_Agent_UCP_Cart_Session();
        $cart_model->update_items($cart_id, $items);

        $cart = $cart_model->get($cart_id);

        return $this->success_response($this->format_cart($cart));
    }

    /**
     * Convert cart to checkout session
     */
    public function convert_to_checkout($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $cart_id = $request->get_param('id');
        $cart = $this->get_cart_or_error($cart_id);

        if (is_wp_error($cart)) {
            return $cart;
        }

        if (empty($cart->items)) {
            return $this->error_response(
                'empty_cart',
                __('Cart is empty.', 'shopping-agent-with-ucp'),
                400
            );
        }

        $shipping_address = $request->get_param('shipping_address');
        $billing_address = $request->get_param('billing_address') ?: $shipping_address;

        // Validate required address fields
        $required_fields = array('first_name', 'last_name', 'address_1', 'city', 'country', 'email');
        foreach ($required_fields as $field) {
            if (empty($shipping_address[$field])) {
                return $this->error_response(
                    'missing_address_field',
                    sprintf(
                        /* translators: %s: field name */
                        __('Shipping address field "%s" is required.', 'shopping-agent-with-ucp'),
                        $field
                    ),
                    400
                );
            }
        }

        // Create checkout session
        $checkout = new Shopping_Agent_UCP_Checkout();
        $session = $checkout->create_session_from_cart($cart, $shipping_address, $billing_address);

        if (is_wp_error($session)) {
            return $session;
        }

        // Update cart status
        $cart_model = new Shopping_Agent_UCP_Cart_Session();
        $cart_model->update_status($cart_id, 'checkout');

        return $this->success_response($session);
    }

    /**
     * Get cart or return error
     */
    private function get_cart_or_error($cart_id)
    {
        $cart_model = new Shopping_Agent_UCP_Cart_Session();
        $cart = $cart_model->get($cart_id);

        if (!$cart) {
            return $this->error_response(
                'cart_not_found',
                __('Cart not found.', 'shopping-agent-with-ucp'),
                404
            );
        }

        // Check if cart is expired
        if (strtotime($cart->expires_at) < time()) {
            return $this->error_response(
                'cart_expired',
                __('Cart has expired.', 'shopping-agent-with-ucp'),
                410
            );
        }

        // Check if cart is still active
        if ($cart->status !== 'active') {
            return $this->error_response(
                'cart_unavailable',
                __('Cart is no longer available.', 'shopping-agent-with-ucp'),
                410
            );
        }

        return $cart;
    }

    /**
     * Format cart for response
     */
    private function format_cart($cart)
    {
        $cart_model = new Shopping_Agent_UCP_Cart_Session();
        $totals = $cart_model->calculate_totals($cart);

        $items = array();
        foreach ($cart->items as $key => $item) {
            $items[] = array_merge(array('key' => $key), $item);
        }

        return array(
            'id' => $cart->id,
            'status' => $cart->status,
            'items' => $items,
            'totals' => $totals,
            'expires_at' => $cart->expires_at,
            'created_at' => $cart->created_at,
            'updated_at' => $cart->updated_at,
        );
    }
}
