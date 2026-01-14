<?php
/**
 * Base REST Controller
 *
 * @package WC_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class WC_UCP_REST_Controller
{

    /**
     * Namespace
     */
    protected $namespace = 'ucp/v1';

    /**
     * Route base
     */
    protected $rest_base = '';

    /**
     * Register routes - must be implemented by child classes
     */
    abstract public function register_routes();

    /**
     * Check if WooCommerce is active
     */
    protected function check_woocommerce()
    {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_not_active',
                __('WooCommerce is not active.', 'ucp-shopping-agent'),
                array('status' => 500)
            );
        }
        return true;
    }

    /**
     * Authenticate request using API key
     *
     * @param WP_REST_Request $request The REST request.
     * @param string $required_permission Required permission level.
     * @return true|WP_Error True if authenticated, WP_Error otherwise.
     */
    protected function authenticate($request, $required_permission = 'read')
    {
        return WC_UCP_Auth::validate_api_key_request($request, $required_permission);
    }

    /**
     * Permission callback for public endpoints
     */
    public function public_permissions_check($request)
    {
        return true;
    }

    /**
     * Permission callback for read endpoints
     */
    public function read_permissions_check($request)
    {
        $auth = $this->authenticate($request, 'read');
        if (is_wp_error($auth)) {
            return $auth;
        }
        return true;
    }

    /**
     * Permission callback for write endpoints
     */
    public function write_permissions_check($request)
    {
        $auth = $this->authenticate($request, 'write');
        if (is_wp_error($auth)) {
            return $auth;
        }
        return true;
    }

    /**
     * Permission callback for admin endpoints
     */
    public function admin_permissions_check($request)
    {
        $auth = $this->authenticate($request, 'admin');
        if (is_wp_error($auth)) {
            return $auth;
        }
        return true;
    }

    /**
     * Format price for UCP (convert to cents/smallest unit)
     *
     * @param mixed $price Price value.
     * @return int Price in smallest currency unit (cents).
     */
    protected function format_price($price)
    {
        $decimals = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2;
        return (int) round(floatval($price) * pow(10, $decimals));
    }

    /**
     * Get currency info
     *
     * @return array Currency information.
     */
    protected function get_currency_info()
    {
        return array(
            'code' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD',
            'symbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$',
            'decimals' => function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2,
        );
    }

    /**
     * Format error response
     */
    protected function error_response($code, $message, $status = 400, $data = array())
    {
        return new WP_Error($code, $message, array_merge(array('status' => $status), $data));
    }

    /**
     * Format success response with metadata
     */
    protected function success_response($data, $meta = array())
    {
        $response = array('data' => $data);
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        return rest_ensure_response($response);
    }

    /**
     * Get pagination parameters
     */
    protected function get_pagination_params($request)
    {
        return array(
            'page' => max(1, (int) $request->get_param('page') ?: 1),
            'per_page' => min(100, max(1, (int) $request->get_param('per_page') ?: 10)),
        );
    }

    /**
     * Format pagination meta
     */
    protected function format_pagination_meta($page, $per_page, $total)
    {
        return array(
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page),
        );
    }
}
