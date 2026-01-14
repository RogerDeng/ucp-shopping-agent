<?php
/**
 * Discovery Endpoint
 *
 * Handles the /.well-known/ucp discovery endpoint.
 *
 * @package WC_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_UCP_Discovery extends WC_UCP_REST_Controller
{

    protected $rest_base = 'discovery';

    /**
     * Register routes
     */
    public function register_routes()
    {
        // Main discovery endpoint
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_discovery'),
            'permission_callback' => array($this, 'public_permissions_check'),
        ));

        // Also register as /manifest for backward compatibility
        register_rest_route($this->namespace, '/manifest', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_discovery'),
            'permission_callback' => array($this, 'public_permissions_check'),
        ));
    }

    /**
     * Get discovery information
     */
    public function get_discovery($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $base_url = get_rest_url(null, $this->namespace);

        return rest_ensure_response(array(
            'ucp' => array(
                'version' => '2026-01-11',
                'merchant' => $this->get_merchant_info(),
                'capabilities' => $this->get_capabilities($base_url),
                'authentication' => $this->get_authentication_info(),
                'rate_limits' => $this->get_rate_limits(),
            ),
        ));
    }

    /**
     * Get merchant information
     */
    private function get_merchant_info()
    {
        $store_address = array(
            'address_1' => get_option('woocommerce_store_address', ''),
            'address_2' => get_option('woocommerce_store_address_2', ''),
            'city' => get_option('woocommerce_store_city', ''),
            'state' => get_option('woocommerce_store_state', ''),
            'postcode' => get_option('woocommerce_store_postcode', ''),
            'country' => get_option('woocommerce_default_country', ''),
        );

        return array(
            'id' => 'merchant_' . wp_hash(get_home_url()),
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => get_home_url(),
            'logo' => $this->get_site_logo(),
            'address' => $store_address,
            'currency' => array(
                'code' => get_woocommerce_currency(),
                'symbol' => get_woocommerce_currency_symbol(),
                'decimals' => wc_get_price_decimals(),
            ),
            'locale' => get_locale(),
            'timezone' => wp_timezone_string(),
        );
    }

    /**
     * Get site logo URL
     */
    private function get_site_logo()
    {
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            return wp_get_attachment_url($logo_id);
        }
        return null;
    }

    /**
     * Get capabilities
     */
    private function get_capabilities($base_url)
    {
        return array(
            array(
                'name' => 'dev.ucp.shopping.discovery',
                'version' => '2026-01-11',
                'endpoint' => $base_url . '/discovery',
                'description' => __('Store discovery and capabilities', 'ucp-shopping-agent'),
            ),
            array(
                'name' => 'dev.ucp.shopping.products',
                'version' => '2026-01-11',
                'endpoint' => $base_url . '/products',
                'description' => __('Browse, search, and filter products', 'ucp-shopping-agent'),
                'operations' => array('list', 'get', 'search'),
            ),
            array(
                'name' => 'dev.ucp.shopping.categories',
                'version' => '2026-01-11',
                'endpoint' => $base_url . '/categories',
                'description' => __('Navigate product categories', 'ucp-shopping-agent'),
                'operations' => array('list', 'get'),
            ),
            array(
                'name' => 'dev.ucp.shopping.cart',
                'version' => '2026-01-11',
                'endpoint' => $base_url . '/carts',
                'description' => __('Persistent cart management', 'ucp-shopping-agent'),
                'operations' => array('create', 'get', 'update', 'delete'),
                'requires_auth' => true,
            ),
            array(
                'name' => 'dev.ucp.shopping.checkout',
                'version' => '2026-01-11',
                'endpoint' => $base_url . '/checkout/sessions',
                'description' => __('Create and manage checkout sessions', 'ucp-shopping-agent'),
                'operations' => array('create', 'get', 'update', 'confirm'),
                'requires_auth' => true,
            ),
            array(
                'name' => 'dev.ucp.shopping.orders',
                'version' => '2026-01-11',
                'endpoint' => $base_url . '/orders',
                'description' => __('Retrieve order details and status', 'ucp-shopping-agent'),
                'operations' => array('list', 'get'),
                'requires_auth' => true,
            ),
            array(
                'name' => 'dev.ucp.shopping.customers',
                'version' => '2026-01-11',
                'endpoint' => $base_url . '/customers',
                'description' => __('Customer profile management', 'ucp-shopping-agent'),
                'operations' => array('create', 'get', 'update'),
                'requires_auth' => true,
            ),
            array(
                'name' => 'dev.ucp.shopping.shipping',
                'version' => '2026-01-11',
                'endpoint' => $base_url . '/shipping/rates',
                'description' => __('Calculate shipping rates', 'ucp-shopping-agent'),
                'operations' => array('calculate'),
            ),
            array(
                'name' => 'dev.ucp.shopping.reviews',
                'version' => '2026-01-11',
                'endpoint' => $base_url . '/reviews',
                'description' => __('Product reviews and ratings', 'ucp-shopping-agent'),
                'operations' => array('list', 'get', 'create'),
            ),
            array(
                'name' => 'dev.ucp.shopping.coupons',
                'version' => '2026-01-11',
                'endpoint' => $base_url . '/coupons',
                'description' => __('Discover and validate promotional codes', 'ucp-shopping-agent'),
                'operations' => array('list', 'validate'),
            ),
            array(
                'name' => 'dev.ucp.shopping.webhooks',
                'version' => '2026-01-11',
                'description' => __('Real-time order event notifications', 'ucp-shopping-agent'),
                'events' => array(
                    'order.created',
                    'order.status_changed',
                    'order.paid',
                    'order.refunded',
                ),
            ),
        );
    }

    /**
     * Get authentication info
     */
    private function get_authentication_info()
    {
        return array(
            'type' => 'api_key',
            'methods' => array(
                array(
                    'name' => 'header',
                    'header_name' => 'X-UCP-API-Key',
                    'format' => 'key_id:secret',
                ),
                array(
                    'name' => 'query',
                    'param_name' => 'ucp_api_key',
                    'format' => 'key_id:secret',
                ),
            ),
            'permissions' => array(
                array(
                    'level' => 'read',
                    'description' => __('Browse products, categories, reviews', 'ucp-shopping-agent'),
                ),
                array(
                    'level' => 'write',
                    'description' => __('Create carts, checkout sessions, orders', 'ucp-shopping-agent'),
                ),
                array(
                    'level' => 'admin',
                    'description' => __('Manage API keys, access all endpoints', 'ucp-shopping-agent'),
                ),
            ),
        );
    }

    /**
     * Get rate limits info
     */
    private function get_rate_limits()
    {
        $rate_limit = (int) get_option('wc_ucp_rate_limit', 100);

        return array(
            'requests_per_minute' => $rate_limit,
            'burst_limit' => $rate_limit * 2,
        );
    }
}
