<?php
/**
 * Orders Endpoint
 *
 * @package Shopping_Agent_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class Shopping_Agent_UCP_Orders extends Shopping_Agent_UCP_REST_Controller
{

    protected $rest_base = 'orders';

    /**
     * Register routes
     */
    public function register_routes()
    {
        // List orders
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_orders'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'page' => array(
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                ),
                'per_page' => array(
                    'type' => 'integer',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 100,
                ),
                'status' => array(
                    'type' => 'string',
                    'enum' => array('pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed'),
                ),
                'customer_id' => array(
                    'type' => 'integer',
                ),
            ),
        ));

        // Get order by ID
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_order'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && $value > 0;
                    },
                ),
            ),
        ));

        // Get order events/timeline
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/events', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_order_events'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && $value > 0;
                    },
                ),
            ),
        ));
    }

    /**
     * Get orders list
     */
    public function get_orders($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $pagination = $this->get_pagination_params($request);

        $args = array(
            'limit' => $pagination['per_page'],
            'page' => $pagination['page'],
            'orderby' => 'date',
            'order' => 'DESC',
        );

        // Filter by status
        if ($status = $request->get_param('status')) {
            $args['status'] = $status;
        }

        // Filter by customer
        if ($customer_id = $request->get_param('customer_id')) {
            $args['customer_id'] = (int) $customer_id;
        }

        // Only show UCP-created orders if using API key
        $api_key = Shopping_Agent_UCP_Auth::get_current_api_key();
        if ($api_key) {
            $args['meta_key'] = '_shopping_agent_shopping_agent_ucp_created';
            $args['meta_value'] = true;
        }

        $orders = wc_get_orders($args);

        // Get total count
        $count_args = $args;
        $count_args['limit'] = -1;
        $count_args['return'] = 'ids';
        $total = count(wc_get_orders($count_args));

        $formatted_orders = array();
        foreach ($orders as $order) {
            $formatted_orders[] = $this->format_order_summary($order);
        }

        return $this->success_response(
            $formatted_orders,
            $this->format_pagination_meta($pagination['page'], $pagination['per_page'], $total)
        );
    }

    /**
     * Get single order
     */
    public function get_order($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $order_id = (int) $request->get_param('id');
        $order = wc_get_order($order_id);

        if (!$order) {
            return $this->error_response(
                'order_not_found',
                __('Order not found.', 'shopping-agent-with-ucp'),
                404
            );
        }

        return $this->success_response($this->format_order($order));
    }

    /**
     * Get order events/timeline
     */
    public function get_order_events($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $order_id = (int) $request->get_param('id');
        $order = wc_get_order($order_id);

        if (!$order) {
            return $this->error_response(
                'order_not_found',
                __('Order not found.', 'shopping-agent-with-ucp'),
                404
            );
        }

        // Get order notes
        $notes = wc_get_order_notes(array(
            'order_id' => $order_id,
            'orderby' => 'date_created',
            'order' => 'ASC',
        ));

        $events = array();

        // Add creation event
        $events[] = array(
            'type' => 'order.created',
            'timestamp' => $order->get_date_created() ? $order->get_date_created()->format('c') : null,
            'message' => __('Order created', 'shopping-agent-with-ucp'),
        );

        // Add note events
        foreach ($notes as $note) {
            $event_type = 'order.note';
            if (strpos(strtolower($note->content), 'status') !== false) {
                $event_type = 'order.status_changed';
            } elseif (strpos(strtolower($note->content), 'payment') !== false) {
                $event_type = 'order.payment';
            }

            $events[] = array(
                'type' => $event_type,
                'timestamp' => $note->date_created ? $note->date_created->format('c') : null,
                'message' => $note->content,
                'added_by' => $note->added_by,
            );
        }

        // Add payment event if paid
        if ($order->is_paid() && $order->get_date_paid()) {
            $events[] = array(
                'type' => 'order.paid',
                'timestamp' => $order->get_date_paid()->format('c'),
                'message' => __('Payment received', 'shopping-agent-with-ucp'),
            );
        }

        // Add completion event if completed
        if ($order->get_status() === 'completed' && $order->get_date_completed()) {
            $events[] = array(
                'type' => 'order.completed',
                'timestamp' => $order->get_date_completed()->format('c'),
                'message' => __('Order completed', 'shopping-agent-with-ucp'),
            );
        }

        // Sort by timestamp
        usort($events, function ($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });

        return $this->success_response(array(
            'order_id' => $order_id,
            'status' => $order->get_status(),
            'events' => $events,
        ));
    }

    /**
     * Format order summary
     */
    private function format_order_summary($order)
    {
        return array(
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'total' => array(
                'amount' => $this->format_price($order->get_total()),
                'currency' => $order->get_currency(),
            ),
            'items_count' => $order->get_item_count(),
            'customer' => array(
                'id' => $order->get_customer_id(),
                'email' => $order->get_billing_email(),
                'name' => $order->get_formatted_billing_full_name(),
            ),
            'created_at' => $order->get_date_created() ? $order->get_date_created()->format('c') : null,
        );
    }

    /**
     * Format full order details
     */
    private function format_order($order)
    {
        $items = array();
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $items[] = array(
                'id' => $item_id,
                'product_id' => $item->get_product_id(),
                'variation_id' => $item->get_variation_id(),
                'name' => $item->get_name(),
                'sku' => $product ? $product->get_sku() : null,
                'quantity' => $item->get_quantity(),
                'price' => $this->format_price($order->get_item_subtotal($item)),
                'total' => $this->format_price($item->get_total()),
                'tax' => $this->format_price($item->get_total_tax()),
            );
        }

        return array(
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'currency' => $order->get_currency(),
            'items' => $items,
            'totals' => array(
                'subtotal' => $this->format_price($order->get_subtotal()),
                'shipping' => $this->format_price($order->get_shipping_total()),
                'tax' => $this->format_price($order->get_total_tax()),
                'discount' => $this->format_price($order->get_discount_total()),
                'total' => $this->format_price($order->get_total()),
            ),
            'customer' => array(
                'id' => $order->get_customer_id(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
            ),
            'shipping_address' => array(
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'company' => $order->get_shipping_company(),
                'address_1' => $order->get_shipping_address_1(),
                'address_2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'postcode' => $order->get_shipping_postcode(),
                'country' => $order->get_shipping_country(),
            ),
            'billing_address' => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'company' => $order->get_billing_company(),
                'address_1' => $order->get_billing_address_1(),
                'address_2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'postcode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
            ),
            'payment_method' => array(
                'id' => $order->get_payment_method(),
                'title' => $order->get_payment_method_title(),
            ),
            'shipping_method' => $order->get_shipping_method(),
            'is_paid' => $order->is_paid(),
            'date_paid' => $order->get_date_paid() ? $order->get_date_paid()->format('c') : null,
            'created_at' => $order->get_date_created() ? $order->get_date_created()->format('c') : null,
            'updated_at' => $order->get_date_modified() ? $order->get_date_modified()->format('c') : null,
        );
    }
}
