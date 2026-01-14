<?php
/**
 * Shipping Endpoint
 *
 * @package WC_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_UCP_Shipping extends WC_UCP_REST_Controller
{

    protected $rest_base = 'shipping';

    /**
     * Register routes
     */
    public function register_routes()
    {
        // Calculate shipping rates
        register_rest_route($this->namespace, '/' . $this->rest_base . '/rates', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'calculate_rates'),
            'permission_callback' => array($this, 'public_permissions_check'),
            'args' => array(
                'destination' => array(
                    'type' => 'object',
                    'required' => true,
                    'properties' => array(
                        'country' => array('type' => 'string', 'required' => true),
                        'state' => array('type' => 'string'),
                        'postcode' => array('type' => 'string'),
                        'city' => array('type' => 'string'),
                    ),
                ),
                'items' => array(
                    'type' => 'array',
                    'required' => true,
                ),
            ),
        ));

        // Get available shipping methods
        register_rest_route($this->namespace, '/' . $this->rest_base . '/methods', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_methods'),
            'permission_callback' => array($this, 'public_permissions_check'),
        ));

        // Get shipping zones
        register_rest_route($this->namespace, '/' . $this->rest_base . '/zones', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_zones'),
            'permission_callback' => array($this, 'public_permissions_check'),
        ));
    }

    /**
     * Calculate shipping rates
     */
    public function calculate_rates($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $destination = $request->get_param('destination');
        $items = $request->get_param('items');

        // Initialize WC session if needed
        if (!WC()->session) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }

        // Initialize WC customer
        if (!WC()->customer) {
            WC()->customer = new WC_Customer();
        }

        // Set destination
        WC()->customer->set_shipping_country($destination['country'] ?? '');
        WC()->customer->set_shipping_state($destination['state'] ?? '');
        WC()->customer->set_shipping_postcode($destination['postcode'] ?? '');
        WC()->customer->set_shipping_city($destination['city'] ?? '');

        // Calculate package contents
        $package_contents = array();
        $package_cost = 0;

        foreach ($items as $index => $item) {
            $product_id = $item['product_id'] ?? null;
            $sku = $item['sku'] ?? null;
            $quantity = (int) ($item['quantity'] ?? 1);

            if (!$product_id && $sku) {
                $product_id = wc_get_product_id_by_sku($sku);
            }

            if (!$product_id) {
                continue;
            }

            $variation_id = $item['variation_id'] ?? 0;
            $product = wc_get_product($variation_id ?: $product_id);

            if (!$product) {
                continue;
            }

            $key = md5($product_id . $variation_id);
            $package_contents[$key] = array(
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'quantity' => $quantity,
                'data' => $product,
                'line_total' => $product->get_price() * $quantity,
                'line_subtotal' => $product->get_price() * $quantity,
            );

            $package_cost += $product->get_price() * $quantity;
        }

        if (empty($package_contents)) {
            return $this->error_response(
                'no_valid_items',
                __('No valid items provided.', 'ucp-shopping-agent'),
                400
            );
        }

        // Build shipping package
        $package = array(
            'contents' => $package_contents,
            'contents_cost' => $package_cost,
            'applied_coupons' => array(),
            'destination' => array(
                'country' => $destination['country'] ?? '',
                'state' => $destination['state'] ?? '',
                'postcode' => $destination['postcode'] ?? '',
                'city' => $destination['city'] ?? '',
                'address' => $destination['address_1'] ?? '',
                'address_2' => $destination['address_2'] ?? '',
            ),
        );

        // Get shipping zone
        $shipping_zone = WC_Shipping_Zones::get_zone_matching_package($package);
        $shipping_methods = $shipping_zone->get_shipping_methods(true);

        $rates = array();

        foreach ($shipping_methods as $method) {
            // Skip disabled methods
            if (!$method->is_enabled()) {
                continue;
            }

            // Calculate rates for this method
            $method->calculate_shipping($package);

            foreach ($method->rates as $rate_id => $rate) {
                $rates[] = array(
                    'id' => $rate_id,
                    'method_id' => $rate->get_method_id(),
                    'instance_id' => $rate->get_instance_id(),
                    'label' => $rate->get_label(),
                    'cost' => array(
                        'amount' => $this->format_price($rate->get_cost()),
                        'currency' => get_woocommerce_currency(),
                    ),
                    'taxes' => $rate->get_taxes(),
                    'meta_data' => $rate->get_meta_data(),
                );
            }
        }

        return $this->success_response(array(
            'destination' => $destination,
            'rates' => $rates,
            'zone' => array(
                'id' => $shipping_zone->get_id(),
                'name' => $shipping_zone->get_zone_name(),
            ),
        ));
    }

    /**
     * Get available shipping methods
     */
    public function get_methods($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $methods = WC()->shipping()->get_shipping_methods();
        $formatted_methods = array();

        foreach ($methods as $method_id => $method) {
            $formatted_methods[] = array(
                'id' => $method_id,
                'title' => $method->get_method_title(),
                'description' => $method->get_method_description(),
                'supports' => $method->supports,
            );
        }

        return $this->success_response($formatted_methods);
    }

    /**
     * Get shipping zones
     */
    public function get_zones($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $zones = WC_Shipping_Zones::get_zones();
        $formatted_zones = array();

        // Add "Rest of World" zone
        $rest_of_world = new WC_Shipping_Zone(0);
        $formatted_zones[] = array(
            'id' => 0,
            'name' => $rest_of_world->get_zone_name(),
            'order' => 0,
            'methods' => $this->format_zone_methods($rest_of_world),
        );

        foreach ($zones as $zone_data) {
            $zone = new WC_Shipping_Zone($zone_data['id']);
            $formatted_zones[] = array(
                'id' => $zone->get_id(),
                'name' => $zone->get_zone_name(),
                'order' => $zone->get_zone_order(),
                'locations' => $this->format_zone_locations($zone),
                'methods' => $this->format_zone_methods($zone),
            );
        }

        return $this->success_response($formatted_zones);
    }

    /**
     * Format zone locations
     */
    private function format_zone_locations($zone)
    {
        $locations = array();

        foreach ($zone->get_zone_locations() as $location) {
            $locations[] = array(
                'code' => $location->code,
                'type' => $location->type,
            );
        }

        return $locations;
    }

    /**
     * Format zone methods
     */
    private function format_zone_methods($zone)
    {
        $methods = array();

        foreach ($zone->get_shipping_methods() as $method) {
            $methods[] = array(
                'instance_id' => $method->get_instance_id(),
                'id' => $method->id,
                'title' => $method->get_title(),
                'enabled' => $method->is_enabled(),
            );
        }

        return $methods;
    }
}
