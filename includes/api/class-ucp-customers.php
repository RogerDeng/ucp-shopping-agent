<?php
/**
 * Customers Endpoint
 *
 * @package WC_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_UCP_Customers extends WC_UCP_REST_Controller
{

    protected $rest_base = 'customers';

    /**
     * Register routes
     */
    public function register_routes()
    {
        // Create customer
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_customer'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'email' => array(
                    'type' => 'string',
                    'required' => true,
                    'format' => 'email',
                ),
                'first_name' => array(
                    'type' => 'string',
                ),
                'last_name' => array(
                    'type' => 'string',
                ),
                'billing' => array(
                    'type' => 'object',
                ),
                'shipping' => array(
                    'type' => 'object',
                ),
            ),
        ));

        // Get customer
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_customer'),
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

        // Update customer
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array($this, 'update_customer'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && $value > 0;
                    },
                ),
                'first_name' => array(
                    'type' => 'string',
                ),
                'last_name' => array(
                    'type' => 'string',
                ),
                'billing' => array(
                    'type' => 'object',
                ),
                'shipping' => array(
                    'type' => 'object',
                ),
            ),
        ));

        // Find customer by email
        register_rest_route($this->namespace, '/' . $this->rest_base . '/email/(?P<email>[^/]+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_customer_by_email'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'email' => array(
                    'type' => 'string',
                    'required' => true,
                ),
            ),
        ));
    }

    /**
     * Create customer
     */
    public function create_customer($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $email = sanitize_email($request->get_param('email'));

        // Check if customer already exists
        $existing = get_user_by('email', $email);
        if ($existing) {
            return $this->error_response(
                'customer_exists',
                __('A customer with this email already exists.', 'ucp-shopping-agent'),
                409,
                array('customer_id' => $existing->ID)
            );
        }

        // Create customer
        $customer = new WC_Customer();
        $customer->set_email($email);

        if ($first_name = $request->get_param('first_name')) {
            $customer->set_first_name(sanitize_text_field($first_name));
        }

        if ($last_name = $request->get_param('last_name')) {
            $customer->set_last_name(sanitize_text_field($last_name));
        }

        // Set billing address
        if ($billing = $request->get_param('billing')) {
            $this->set_customer_address($customer, $billing, 'billing');
        }

        // Set shipping address
        if ($shipping = $request->get_param('shipping')) {
            $this->set_customer_address($customer, $shipping, 'shipping');
        }

        // Generate username from email
        $username = wc_create_new_customer_username($email);
        $customer->set_username($username);

        // Generate random password
        $password = wp_generate_password(16);
        $customer->set_password($password);

        try {
            $customer_id = $customer->save();
        } catch (Exception $e) {
            return $this->error_response(
                'customer_creation_failed',
                $e->getMessage(),
                500
            );
        }

        // Mark as UCP-created
        update_user_meta($customer_id, '_ucp_created', true);

        return $this->success_response($this->format_customer(new WC_Customer($customer_id)));
    }

    /**
     * Get customer
     */
    public function get_customer($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $customer_id = (int) $request->get_param('id');
        $customer = new WC_Customer($customer_id);

        if (!$customer->get_id()) {
            return $this->error_response(
                'customer_not_found',
                __('Customer not found.', 'ucp-shopping-agent'),
                404
            );
        }

        return $this->success_response($this->format_customer($customer));
    }

    /**
     * Update customer
     */
    public function update_customer($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $customer_id = (int) $request->get_param('id');
        $customer = new WC_Customer($customer_id);

        if (!$customer->get_id()) {
            return $this->error_response(
                'customer_not_found',
                __('Customer not found.', 'ucp-shopping-agent'),
                404
            );
        }

        if ($first_name = $request->get_param('first_name')) {
            $customer->set_first_name(sanitize_text_field($first_name));
        }

        if ($last_name = $request->get_param('last_name')) {
            $customer->set_last_name(sanitize_text_field($last_name));
        }

        // Update billing address
        if ($billing = $request->get_param('billing')) {
            $this->set_customer_address($customer, $billing, 'billing');
        }

        // Update shipping address
        if ($shipping = $request->get_param('shipping')) {
            $this->set_customer_address($customer, $shipping, 'shipping');
        }

        try {
            $customer->save();
        } catch (Exception $e) {
            return $this->error_response(
                'customer_update_failed',
                $e->getMessage(),
                500
            );
        }

        return $this->success_response($this->format_customer(new WC_Customer($customer_id)));
    }

    /**
     * Get customer by email
     */
    public function get_customer_by_email($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $email = sanitize_email(urldecode($request->get_param('email')));
        $user = get_user_by('email', $email);

        if (!$user) {
            return $this->error_response(
                'customer_not_found',
                __('Customer not found.', 'ucp-shopping-agent'),
                404
            );
        }

        $customer = new WC_Customer($user->ID);

        return $this->success_response($this->format_customer($customer));
    }

    /**
     * Set customer address
     */
    private function set_customer_address($customer, $address, $type)
    {
        $methods = array(
            'first_name' => "set_{$type}_first_name",
            'last_name' => "set_{$type}_last_name",
            'company' => "set_{$type}_company",
            'address_1' => "set_{$type}_address_1",
            'address_2' => "set_{$type}_address_2",
            'city' => "set_{$type}_city",
            'state' => "set_{$type}_state",
            'postcode' => "set_{$type}_postcode",
            'country' => "set_{$type}_country",
        );

        if ($type === 'billing') {
            $methods['email'] = 'set_billing_email';
            $methods['phone'] = 'set_billing_phone';
        }

        foreach ($methods as $key => $method) {
            if (isset($address[$key]) && method_exists($customer, $method)) {
                $customer->$method(sanitize_text_field($address[$key]));
            }
        }
    }

    /**
     * Format customer for response
     */
    private function format_customer($customer)
    {
        return array(
            'id' => $customer->get_id(),
            'email' => $customer->get_email(),
            'first_name' => $customer->get_first_name(),
            'last_name' => $customer->get_last_name(),
            'username' => $customer->get_username(),
            'billing' => array(
                'first_name' => $customer->get_billing_first_name(),
                'last_name' => $customer->get_billing_last_name(),
                'company' => $customer->get_billing_company(),
                'address_1' => $customer->get_billing_address_1(),
                'address_2' => $customer->get_billing_address_2(),
                'city' => $customer->get_billing_city(),
                'state' => $customer->get_billing_state(),
                'postcode' => $customer->get_billing_postcode(),
                'country' => $customer->get_billing_country(),
                'email' => $customer->get_billing_email(),
                'phone' => $customer->get_billing_phone(),
            ),
            'shipping' => array(
                'first_name' => $customer->get_shipping_first_name(),
                'last_name' => $customer->get_shipping_last_name(),
                'company' => $customer->get_shipping_company(),
                'address_1' => $customer->get_shipping_address_1(),
                'address_2' => $customer->get_shipping_address_2(),
                'city' => $customer->get_shipping_city(),
                'state' => $customer->get_shipping_state(),
                'postcode' => $customer->get_shipping_postcode(),
                'country' => $customer->get_shipping_country(),
            ),
            'is_paying_customer' => $customer->get_is_paying_customer(),
            'orders_count' => $customer->get_order_count(),
            'total_spent' => $this->format_price($customer->get_total_spent()),
            'created_at' => $customer->get_date_created() ? $customer->get_date_created()->format('c') : null,
        );
    }
}
