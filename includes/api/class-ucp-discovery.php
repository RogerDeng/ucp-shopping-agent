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
                'services' => $this->get_services($base_url),
                'capabilities' => $this->get_capabilities(),
            ),
            'merchant' => $this->get_merchant_info(),
            'signing_keys' => $this->get_signing_keys(),
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
     * Get services definition per UCP spec
     */
    private function get_services($base_url)
    {
        return array(
            'dev.ucp.shopping' => array(
                'version' => '2026-01-11',
                'spec' => 'https://ucp.dev/specification/overview',
                'rest' => array(
                    'schema' => 'https://ucp.dev/services/shopping/rest.openapi.json',
                    'endpoint' => rtrim($base_url, '/'),
                ),
            ),
        );
    }

    /**
     * Get capabilities per UCP spec
     * Each capability has: name, version, spec, schema
     */
    private function get_capabilities()
    {
        $spec_base = 'https://ucp.dev/specification';
        $schema_base = 'https://ucp.dev/schemas/shopping';

        return array(
            // Discovery capability
            array(
                'name' => 'dev.ucp.shopping.discovery',
                'version' => '2026-01-11',
                'spec' => $spec_base . '/discovery',
                'schema' => $schema_base . '/discovery.json',
            ),
            // Products capability
            array(
                'name' => 'dev.ucp.shopping.products',
                'version' => '2026-01-11',
                'spec' => $spec_base . '/products',
                'schema' => $schema_base . '/products.json',
            ),
            // Categories capability
            array(
                'name' => 'dev.ucp.shopping.categories',
                'version' => '2026-01-11',
                'spec' => $spec_base . '/categories',
                'schema' => $schema_base . '/categories.json',
            ),
            // Cart capability (extends checkout)
            array(
                'name' => 'dev.ucp.shopping.cart',
                'version' => '2026-01-11',
                'spec' => $spec_base . '/cart',
                'schema' => $schema_base . '/cart.json',
                'extends' => 'dev.ucp.shopping.checkout',
            ),
            // Checkout capability
            array(
                'name' => 'dev.ucp.shopping.checkout',
                'version' => '2026-01-11',
                'spec' => $spec_base . '/checkout',
                'schema' => $schema_base . '/checkout.json',
            ),
            // Order capability
            array(
                'name' => 'dev.ucp.shopping.order',
                'version' => '2026-01-11',
                'spec' => $spec_base . '/order',
                'schema' => $schema_base . '/order.json',
            ),
            // Customers capability
            array(
                'name' => 'dev.ucp.shopping.customers',
                'version' => '2026-01-11',
                'spec' => $spec_base . '/customers',
                'schema' => $schema_base . '/customers.json',
            ),
            // Shipping capability
            array(
                'name' => 'dev.ucp.shopping.shipping',
                'version' => '2026-01-11',
                'spec' => $spec_base . '/shipping',
                'schema' => $schema_base . '/shipping.json',
            ),
            // Reviews capability
            array(
                'name' => 'dev.ucp.shopping.reviews',
                'version' => '2026-01-11',
                'spec' => $spec_base . '/reviews',
                'schema' => $schema_base . '/reviews.json',
            ),
            // Coupons/Discount capability
            array(
                'name' => 'dev.ucp.shopping.discount',
                'version' => '2026-01-11',
                'spec' => $spec_base . '/discount',
                'schema' => $schema_base . '/discount.json',
                'extends' => 'dev.ucp.shopping.checkout',
            ),
            // Fulfillment capability (extends checkout)
            array(
                'name' => 'dev.ucp.shopping.fulfillment',
                'version' => '2026-01-11',
                'spec' => $spec_base . '/fulfillment',
                'schema' => $schema_base . '/fulfillment.json',
                'extends' => 'dev.ucp.shopping.checkout',
            ),
            // Webhooks capability
            array(
                'name' => 'dev.ucp.shopping.webhooks',
                'version' => '2026-01-11',
                'spec' => $spec_base . '/webhooks',
                'schema' => $schema_base . '/webhooks.json',
            ),
        );
    }

    /**
     * Get signing keys for webhook verification (JWK format)
     * Uses keys generated during plugin activation
     */
    private function get_signing_keys()
    {
        return WC_UCP_Activator::get_signing_key_info();
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
